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
        'transaction_purpose',
        'transaction_date',
        'rating',
        'message',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['transaction_data'];

    /**
     * Get the user that owns the feedback
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the transaction data based on stored reference
     */
    public function getTransactionDataAttribute()
    {
        if (!$this->transaction_purpose || !$this->transaction_date) {
            return null;
        }

        // Normalize the date for consistent comparison
        $normalizedDate = \Carbon\Carbon::parse($this->transaction_date)->format('Y-m-d');

        return Transaction::where('user_id', $this->user_id)
            ->where('purpose', $this->transaction_purpose)
            ->whereDate('schedule_date', $normalizedDate)
            ->first();
    }
}
