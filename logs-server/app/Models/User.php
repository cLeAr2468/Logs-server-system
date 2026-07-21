<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'student_id',
        'fname',
        'mname',
        'lname',
        'email',
        'barangay',
        'municipality',
        'province',
        'course',
        'year_level',
        'address',
        'status',
        'profile',
        'password',
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
        ];
    }

    // Accessor to get full name
    public function getFullNameAttribute()
    {
        return trim("{$this->fname} {$this->mname} {$this->lname}");
    }

    /**
     * Get the transactions for the user
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * Get the feedback for the user
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }

    /**
     * Get the announcements for the user
     */
    public function announcements()
    {
        return $this->hasMany(Announcement::class);
    }
}