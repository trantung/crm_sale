<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_SALE = 'sale';

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSale(): bool
    {
        return $this->role === self::ROLE_SALE;
    }

    public function assignedLeads(): HasMany
    {
        return $this->hasMany(Lead::class, 'owner_id');
    }

    public function assignedOrders(): HasMany
    {
        return $this->hasMany(Order::class, 'owner_id');
    }

    public function roleLabel(): string
    {
        return $this->isAdmin() ? 'Quản lý' : 'Tư vấn viên';
    }
}
