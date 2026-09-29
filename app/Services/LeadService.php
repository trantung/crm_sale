<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadSource;
use App\Models\LeadStage;
use App\Models\LeadStageHistory;
use App\Models\LeadTouch;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadService
{
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?: '';

        return $digits !== '' ? $digits : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{lead: Lead, created: bool}
     */
    public function ingest(array $payload, string $channel = 'api', ?User $actor = null): array
    {
        $phone = trim((string) ($payload['phone'] ?? ''));
        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        $normalized = self::normalizePhone($phone);

        if ($normalized === null && $email === '') {
            $nameOnly = (bool) ($payload['allow_name_only'] ?? false);
            if (! $nameOnly || trim((string) ($payload['name'] ?? '')) === '') {
                throw ValidationException::withMessages([
                    'phone' => 'Cần ít nhất số điện thoại hoặc email.',
                ]);
            }
        }

        $source = $this->resolveSource($payload['source_code'] ?? null);
        $defaultStage = LeadStage::query()->where('is_default', true)->orderBy('sort_order')->first()
            ?? LeadStage::query()->orderBy('sort_order')->firstOrFail();

        return DB::transaction(function () use ($payload, $channel, $actor, $phone, $email, $normalized, $source, $defaultStage) {
            $lead = $this->findExisting($normalized, $email);

            $ownerId = $actor?->isSale()
                ? $actor->id
                : ($payload['owner_id'] ?? null);

            $created = false;
            if (! $lead) {
                $lead = Lead::query()->create([
                    'name' => trim((string) ($payload['name'] ?? 'Chưa rõ tên')) ?: 'Chưa rõ tên',
                    'phone' => $phone !== '' ? $phone : null,
                    'phone_normalized' => $normalized,
                    'email' => $email !== '' ? $email : null,
                    'sso_id' => $payload['sso_id'] ?? null,
                    'source_id' => $source?->id,
                    'stage_id' => $defaultStage->id,
                    'owner_id' => $ownerId,
                    'created_by' => $actor?->id,
                    'interested_product' => $payload['interested_product'] ?? null,
                    'note' => $payload['note'] ?? null,
                    'call_result' => 'not_called',
                    'origin' => $channel === 'api' ? 'api' : 'crm',
                    'assigned_at' => $ownerId ? now() : null,
                ]);
                $created = true;
                $lead->code = Lead::assignCode($lead);
                $lead->save();

                LeadStageHistory::query()->create([
                    'lead_id' => $lead->id,
                    'from_stage_id' => null,
                    'to_stage_id' => $defaultStage->id,
                    'changed_by' => $actor?->id,
                    'reason' => $channel === 'api' ? 'Lead vào từ API phễu' : 'Tạo lead trên CRM',
                ]);
            } else {
                $updates = [];
                if (! empty($payload['name']) && ($lead->name === 'Chưa rõ tên' || $lead->name === '')) {
                    $updates['name'] = trim((string) $payload['name']);
                }
                if ($phone !== '' && ! $lead->phone) {
                    $updates['phone'] = $phone;
                    $updates['phone_normalized'] = $normalized;
                }
                if ($email !== '' && ! $lead->email) {
                    $updates['email'] = $email;
                }
                if (! empty($payload['interested_product']) && ! $lead->interested_product) {
                    $updates['interested_product'] = $payload['interested_product'];
                }
                if (! $lead->source_id && $source) {
                    $updates['source_id'] = $source->id;
                }
                $lead->fill($updates);
                $lead->touch();
                $lead->save();
            }

            $touch = $this->recordTouch($lead, $payload, $channel);

            if (! $lead->first_touch_id) {
                $lead->first_touch_id = $touch->id;
            }
            $lead->last_touch_id = $touch->id;
            $lead->save();

            $note = trim((string) ($payload['note'] ?? ''));
            if ($note !== '' && $created) {
                LeadActivity::query()->create([
                    'lead_id' => $lead->id,
                    'user_id' => $actor?->id,
                    'type' => 'note',
                    'content' => $note,
                ]);
            }

            return ['lead' => $lead->fresh(['source', 'stage', 'owner', 'firstTouch', 'lastTouch']), 'created' => $created];
        });
    }

    public function changeStage(Lead $lead, int $toStageId, ?User $actor, ?string $reason = null): Lead
    {
        $toStage = LeadStage::query()->findOrFail($toStageId);
        $reason = is_string($reason) ? trim($reason) : null;
        $reason = $reason !== '' ? $reason : null;
        $sameStage = (int) $lead->stage_id === (int) $toStage->id;

        if ($sameStage && $reason === null) {
            return $lead;
        }

        if (! $sameStage && in_array($toStage->level_group, ['L3', 'L4', 'L5', 'L6'], true) && ! $lead->phone && ! $lead->phone_normalized) {
            throw ValidationException::withMessages([
                'stage_id' => 'Cần số điện thoại trước khi chuyển sang '.$toStage->name.'.',
            ]);
        }

        $fromId = $lead->stage_id;
        if (! $sameStage) {
            $lead->stage_id = $toStage->id;
            $lead->save();
        }

        LeadStageHistory::query()->create([
            'lead_id' => $lead->id,
            'from_stage_id' => $fromId,
            'to_stage_id' => $toStage->id,
            'changed_by' => $actor?->id,
            'reason' => $reason,
        ]);

        if ($reason) {
            LeadActivity::query()->create([
                'lead_id' => $lead->id,
                'user_id' => $actor?->id,
                'type' => 'note',
                'content' => ($sameStage ? 'Ghi chú level '.$toStage->name : 'Chuyển level sang '.$toStage->name).': '.$reason,
            ]);
        }

        return $lead->fresh(['stage']);
    }

    public function assign(Lead $lead, ?int $ownerId, User $actor): Lead
    {
        $owner = $ownerId ? User::query()->where('role', User::ROLE_SALE)->where('is_active', true)->findOrFail($ownerId) : null;

        $lead->owner_id = $owner?->id;
        $lead->assigned_at = $owner ? now() : null;
        $lead->save();

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $actor->id,
            'type' => 'note',
            'content' => $owner
                ? 'Quản lý phân lead cho TVV '.$owner->name.' ('.$owner->username.')'
                : 'Quản lý bỏ phân công lead',
        ]);

        return $lead->fresh(['owner']);
    }

    public function logCall(Lead $lead, User $actor, string $result, ?string $note = null, ?string $callbackAt = null): Lead
    {
        if (! array_key_exists($result, Lead::CALL_RESULTS)) {
            throw ValidationException::withMessages([
                'call_result' => 'Kết quả cuộc gọi không hợp lệ.',
            ]);
        }

        $lead->call_result = $result;
        $lead->last_called_at = now();
        $lead->callback_at = $result === 'callback' && $callbackAt
            ? $callbackAt
            : ($result === 'callback' ? $lead->callback_at : null);
        $lead->save();

        $content = 'Kết quả gọi: '.Lead::CALL_RESULTS[$result];
        if ($lead->callback_at && $result === 'callback') {
            $content .= ' — hẹn '.$lead->callback_at->format('d/m/Y H:i');
        }
        if ($note) {
            $content .= '. '.$note;
        }

        LeadActivity::query()->create([
            'lead_id' => $lead->id,
            'user_id' => $actor->id,
            'type' => 'call',
            'content' => $content,
        ]);

        return $lead->fresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordTouch(Lead $lead, array $payload, string $channel): LeadTouch
    {
        return LeadTouch::query()->create([
            'lead_id' => $lead->id,
            'kind' => $lead->first_touch_id ? 'return' : 'first',
            'utm_source' => Arr::get($payload, 'utm_source'),
            'utm_medium' => Arr::get($payload, 'utm_medium'),
            'utm_campaign' => Arr::get($payload, 'utm_campaign'),
            'utm_content' => Arr::get($payload, 'utm_content'),
            'utm_term' => Arr::get($payload, 'utm_term'),
            'landing_url' => Arr::get($payload, 'landing_url'),
            'referrer' => Arr::get($payload, 'referrer'),
            'gclid' => Arr::get($payload, 'gclid'),
            'fbclid' => Arr::get($payload, 'fbclid'),
            'ip_address' => Arr::get($payload, 'ip_address'),
            'user_agent' => Arr::get($payload, 'user_agent'),
            'channel' => $channel,
        ]);
    }

    private function findExisting(?string $normalized, string $email): ?Lead
    {
        if ($normalized) {
            $byPhone = Lead::query()->where('phone_normalized', $normalized)->first();
            if ($byPhone) {
                return $byPhone;
            }
        }

        if ($email !== '') {
            return Lead::query()->where('email', $email)->first();
        }

        return null;
    }

    private function resolveSource(?string $code): ?LeadSource
    {
        $code = trim((string) $code);
        if ($code === '') {
            return LeadSource::query()->where('code', 'website')->first();
        }

        return LeadSource::query()->where('code', $code)->first()
            ?? LeadSource::query()->where('code', 'other')->first();
    }
}
