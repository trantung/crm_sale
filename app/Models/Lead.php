<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Lead extends Model
{
    use SoftDeletes;

    public const CUSTOMER_TYPES = [
        'student' => 'Học sinh',
        'university_student' => 'Sinh viên',
        'parent' => 'Phụ huynh mua cho con',
        'working' => 'Người đi làm',
    ];

    public const CALL_RESULTS = [
        'not_called' => 'Chưa gọi',
        'no_answer' => 'Tắt máy',
        'busy' => 'Máy bận',
        'wrong_number' => 'Sai số',
        'callback' => 'Hẹn gọi lại',
        'interested' => 'Quan tâm (Báo giá)',
        'not_interested' => 'Không quan tâm',
    ];

    protected $fillable = [
        'code',
        'name',
        'phone',
        'phone_normalized',
        'email',
        'sso_id',
        'source_id',
        'stage_id',
        'owner_id',
        'created_by',
        'interested_product',
        'customer_type',
        'note',
        'call_result',
        'last_called_at',
        'callback_at',
        'first_touch_id',
        'last_touch_id',
        'origin',
        'assigned_at',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'last_called_at' => 'datetime',
            'callback_at' => 'datetime',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class, 'source_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(LeadStage::class, 'stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function firstTouch(): BelongsTo
    {
        return $this->belongsTo(LeadTouch::class, 'first_touch_id');
    }

    public function lastTouch(): BelongsTo
    {
        return $this->belongsTo(LeadTouch::class, 'last_touch_id');
    }

    public function leadTouches(): HasMany
    {
        return $this->hasMany(LeadTouch::class)->orderByDesc('id');
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(LeadStageHistory::class)->orderByDesc('id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->orderByDesc('id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('owner_id', $user->id);
    }

    public function scopeLevel(Builder $query, ?string $level): Builder
    {
        if (! $level || ! array_key_exists($level, LeadStage::levelTabs())) {
            return $query;
        }

        return $query->whereHas('stage', fn (Builder $stage) => $stage->where('level_group', $level));
    }

    public function scopeTab(Builder $query, ?string $tab): Builder
    {
        return match ($tab) {
            'new' => $query->whereNull('last_called_at'),
            'callback' => $query->whereNotNull('callback_at')->where('callback_at', '>=', now()->startOfDay()),
            'recare' => $query->whereNotNull('last_called_at')
                ->where(function (Builder $inner) {
                    $inner->whereNull('callback_at')
                        ->orWhere('callback_at', '<', now()->startOfDay());
                })
                ->where(function (Builder $inner) {
                    $inner->whereIn('call_result', ['no_answer', 'busy', 'not_called'])
                        ->orWhere('last_called_at', '<', now()->subDay());
                }),
            default => $query,
        };
    }

    public function customerTypeLabel(): string
    {
        if (! $this->customer_type) {
            return '—';
        }

        return self::CUSTOMER_TYPES[$this->customer_type] ?? $this->customer_type;
    }

    public function formattedPhone(): string
    {
        $digits = $this->phone_normalized ?: preg_replace('/\D+/', '', (string) $this->phone);
        if (strlen((string) $digits) === 10) {
            return substr($digits, 0, 4).'.'.substr($digits, 4, 3).'.'.substr($digits, 7);
        }

        return $this->phone ?: '—';
    }

    public function telHref(): ?string
    {
        $digits = $this->phone_normalized ?: preg_replace('/\D+/', '', (string) $this->phone);

        return $digits ? 'tel:'.$digits : null;
    }

    public function callStatusLabel(): string
    {
        if ($this->call_result === 'callback' && $this->callback_at) {
            return 'Hẹn '.$this->callback_at->format('H:i');
        }

        $result = $this->call_result ?: 'not_called';

        return self::CALL_RESULTS[$result] ?? 'Chưa gọi';
    }

    public function callStatusTone(): string
    {
        if ($this->call_result === 'callback') {
            return 'warn';
        }

        return match ($this->call_result) {
            'interested' => 'ok',
            'wrong_number', 'not_interested' => 'mute',
            'no_answer', 'busy' => 'bad',
            default => 'bad',
        };
    }

    public function lastCallLabel(): string
    {
        if (! $this->last_called_at) {
            return 'Chưa gọi';
        }

        if ($this->last_called_at->isToday()) {
            return 'Hôm nay '.$this->last_called_at->format('H:i');
        }

        if ($this->last_called_at->isYesterday()) {
            return 'Hôm qua '.$this->last_called_at->format('H:i');
        }

        return $this->last_called_at->format('d/m H:i');
    }

    public static function assignCode(Lead $lead): string
    {
        $year = ($lead->created_at ?? Carbon::now())->format('Y');

        return 'LD-'.$year.$lead->id;
    }
}
