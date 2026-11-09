<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The primary key for the model.
     */
    protected $primaryKey = 'student_id';

    /**
     * The "type" of the primary key ID.
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    protected $fillable = [
        'student_id',
        'suffix',
        'region',
        'province',
        'municipality',
        'barangay',
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
            // Encrypt address fields
            'region' => 'encrypted',
            'province' => 'encrypted',
            'municipality' => 'encrypted',
            'barangay' => 'encrypted',
        ];
    }

    /**
     * Relationship to Masterlist
     */
    public function masterlist()
    {
        return $this->belongsTo(Masterlist::class, 'student_id', 'student_id');
    }

    // Accessor to get full name from masterlist relationship
    public function getFullNameAttribute()
    {
        if ($this->masterlist) {
            return trim("{$this->masterlist->fname} {$this->masterlist->mname} {$this->masterlist->lname}");
        }
        return '';
    }

    // Accessors for masterlist fields for backward compatibility
    public function getFnameAttribute()
    {
        return $this->masterlist?->fname;
    }

    public function getMnameAttribute()
    {
        return $this->masterlist?->mname;
    }

    public function getLnameAttribute()
    {
        return $this->masterlist?->lname;
    }

    public function getEmailAttribute()
    {
        return $this->masterlist?->email;
    }

    public function getCourseAttribute()
    {
        return $this->masterlist?->course;
    }

    public function getYearLevelAttribute()
    {
        return $this->masterlist?->year_level;
    }

    /**
     * Get the transactions for the user
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'student_id', 'student_id');
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