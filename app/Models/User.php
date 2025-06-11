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
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'partner_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
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
    ];
    
    /**
     * Проверяет, является ли пользователь администратором.
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    /**
     * Проверяет, является ли пользователь партнером.
     *
     * @return bool
     */
    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }
    
    /**
     * Проверяет, является ли пользователь клиентом.
     *
     * @return bool
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }
    
    /**
     * Проверяет, является ли пользователь сметчиком.
     *
     * @return bool
     */
    public function isEstimator(): bool
    {
        return $this->role === 'estimator';
    }
    
    /**
     * Получает URL аватара пользователя.
     *
     * @return string
     */
    public function getAvatarUrl(): string
    {
        if ($this->avatar) {
            return asset('storage/avatars/' . $this->avatar);
        }
        
        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&color=7F9CF5&background=EBF4FF';
    }
    
    /**
     * Получает проекты, связанные с клиентом по номеру телефона.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function clientProjects()
    {
        return $this->hasMany(Project::class, 'phone', 'phone');
    }
}
