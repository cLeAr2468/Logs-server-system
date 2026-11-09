<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'date_requested',
        'request_type',
        'purpose_id',
        'schedule_date',
        'release_date',
        'time_slot',
        'status',
        'rejection_reason',
    ];

    protected $casts = [
        'date_requested' => 'datetime',
        'schedule_date' => 'date',
        'release_date' => 'date',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'student_id', 'student_id');
    }

    /**
     * Get the purpose for this transaction (Pure Relationship)
     */
    public function purpose()
    {
        return $this->belongsTo(Purpose::class, 'purpose_id', 'id');
    }

    /**
     * Accessors for address fields from user relationship
     * These provide backward compatibility for existing code
     */
    public function getRegionAttribute()
    {
        return $this->user?->region;
    }

    public function getProvinceAttribute()
    {
        return $this->user?->province;
    }

    public function getMunicipalityAttribute()
    {
        return $this->user?->municipality;
    }

    public function getBarangayAttribute()
    {
        return $this->user?->barangay;
    }

    public function getBrgyAttribute()
    {
        return $this->user?->barangay; // Alias for barangay
    }

    /**
     * Get the feedback for this transaction
     */
    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'transact_id', 'id');
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('schedule_date', $date);
    }

    /**
     * Scope to filter pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to filter approved transactions
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
}
