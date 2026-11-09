<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Purpose extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
    ];

    /**
     * Get all transactions for this purpose (Pure Relationship)
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'purpose_id', 'id');
    }
}
