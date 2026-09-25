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
     * Get the transaction data dynamically based on user's most recent completed transaction
     */
    public function getTransactionDataAttribute()
    {
        return Transaction::where('user_id', $this->user_id)
            ->where('status', 'completed')
            ->where('created_at', '<=', $this->created_at)
            ->orderBy('created_at', 'desc')
            ->first();
    }
}
