<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LeadIntakeController extends Controller
{
    public function store(Request $request, LeadService $leads): JsonResponse
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
            'sso_id' => ['nullable', 'string', 'max:64'],
            'source_code' => ['nullable', 'string', 'max:64'],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
            'landing_url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'gclid' => ['nullable', 'string', 'max:255'],
            'fbclid' => ['nullable', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'interested_product' => ['nullable', 'string', 'max:64'],
        ]);

        if (empty($data['phone']) && empty($data['email'])) {
            throw ValidationException::withMessages([
                'phone' => 'Cần ít nhất số điện thoại hoặc email.',
            ]);
        }

        $data['ip_address'] = $request->ip();
        $data['user_agent'] = substr((string) $request->userAgent(), 0, 512);

        $result = $leads->ingest($data, 'api');
        $lead = $result['lead'];

        return response()->json([
            'status' => true,
            'message' => $result['created'] ? 'Lead created' : 'Lead updated (return touch)',
            'created' => $result['created'],
            'data' => [
                'id' => $lead->id,
                'name' => $lead->name,
                'phone' => $lead->phone,
                'email' => $lead->email,
                'stage' => $lead->stage?->slug,
                'source' => $lead->source?->code,
                'owner_id' => $lead->owner_id,
                'first_touch' => $lead->firstTouch?->only([
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                    'landing_url', 'gclid', 'fbclid', 'created_at',
                ]),
                'last_touch' => $lead->lastTouch?->only([
                    'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
                    'landing_url', 'gclid', 'fbclid', 'created_at',
                ]),
            ],
        ], $result['created'] ? 201 : 200);
    }
}
