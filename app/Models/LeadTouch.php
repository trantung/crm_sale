<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadTouch extends Model
{
    protected $fillable = [
        'lead_id',
        'kind',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_content',
        'utm_term',
        'landing_url',
        'referrer',
        'gclid',
        'fbclid',
        'ip_address',
        'user_agent',
        'channel',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function utmSummary(): string
    {
        $parts = array_filter([
            $this->utm_source,
            $this->utm_medium,
            $this->utm_campaign,
        ]);

        return $parts ? implode(' / ', $parts) : '—';
    }
}
