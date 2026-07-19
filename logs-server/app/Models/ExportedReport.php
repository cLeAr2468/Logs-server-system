<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExportedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'report_name',
        'report_type',
        'file_format',
        'file_path',
        'file_size',
        'start_date',
        'end_date',
        'include_summary',
        'include_details',
        'include_feedback',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'include_summary' => 'boolean',
        'include_details' => 'boolean',
        'include_feedback' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the staff that generated the report
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
