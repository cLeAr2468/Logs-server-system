<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'purpose',
        'street_house_no',
        'brgy',
        'municipality',
        'province',
        'schedule_date',
        'time_slot',
        'status',
    ];

    protected $casts = [
        'schedule_date' => 'date',
    ];

    /**
     * Get the user that owns the transaction
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
