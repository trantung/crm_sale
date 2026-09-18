<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public const TYPES = [
        'note' => 'Ghi chú',
        'call' => 'Gọi điện',
        'chat' => 'Chat',
        'meeting' => 'Hẹn gặp',
    ];

    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'content',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
