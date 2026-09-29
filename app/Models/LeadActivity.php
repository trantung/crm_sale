<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends Model
{
    public const TYPES = [
        'call' => 'Gọi điện',
        'zalo' => 'Nhắn tin Zalo',
        'sms' => 'Nhắn tin SMS',
        'facebook' => 'Nhắn tin Facebook',
        'email' => 'Email',
        'note' => 'Ghi chú',
        'chat' => 'Chat',
        'meeting' => 'Hẹn gặp',
    ];

    public const CONTACT_TYPES = [
        'call' => 'Gọi điện',
        'zalo' => 'Nhắn tin Zalo',
        'sms' => 'Nhắn tin SMS',
        'facebook' => 'Nhắn tin Facebook',
        'email' => 'Email',
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
