<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
        'status',
        'is_active',
        'contact',
        'disability',
        'photo_url',
        'photo_path',
        'company_id',
        'company_name',
        'company_address',
        'company_industry',
        'company_login_code',
        'position',
        'department',
        'last_login_at',
        'last_logout_at',
        'password_reset_requested_at',
        'active_session_key',
        'session_last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'company_login_code',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'last_logout_at' => 'datetime',
        'password_reset_requested_at' => 'datetime',
        'session_last_seen_at' => 'datetime',
    ];
}
