<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'transaction_id',
        'type',
        'title',
        'message',
        'reason',
        'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the transaction (Pure Relationship)
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Scope for unread notifications
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope for client notifications
     */
    public function scopeForClient($query, $studentId)
    {
        return $query->where('recipient_type', 'client')
                     ->where('recipient_id', $studentId);
    }

    /**
     * Scope for admin notifications
     */
    public function scopeForAdmin($query)
    {
        return $query->where('recipient_type', 'admin');
    }
}
