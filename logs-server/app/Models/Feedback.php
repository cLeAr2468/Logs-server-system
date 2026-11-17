<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'feedback';

    protected $fillable = [
        'user_id',
        'transaction_id',
        'purpose_id',
        'rating',
        'message',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the feedback
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Get the transaction that this feedback belongs to
     */
    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id', 'id');
    }

    /**
     * Get the purpose relationship
     */
    public function purpose()
    {
        return $this->belongsTo(Purpose::class, 'purpose_id', 'id');
    }
    
    /**
     * Append transaction data with purpose for display
     */
    protected $appends = ['transaction_data'];
    
    public function getTransactionDataAttribute()
    {
        if ($this->transaction) {
            return [
                'id' => $this->transaction->id,
                'purpose' => $this->purpose ? $this->purpose->name : null,
                'appointment_date' => $this->transaction->appointment_date,
                'appointment_time' => $this->transaction->appointment_time,
                'status' => $this->transaction->status,
            ];
        }
        return null;
    }
}
