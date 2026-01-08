<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // 🟢 Gebruik UUID als primary key
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * Mass assignable attributes
     */
    protected $fillable = [
        'id',      // UUID
        'name',
        'email',
        'password',
        'role',    // student/docent/admin
    ];

    /**
     * Hidden attributes
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Laravel automatisch hash
        ];
    }

    // =========================
    // RELATIES
    // =========================

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function docent()
    {
        return $this->hasOne(Docent::class);
    }

    // =========================
    // ROLE HELPERS
    // =========================

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isDocent(): bool
    {
        return $this->role === 'docent';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
