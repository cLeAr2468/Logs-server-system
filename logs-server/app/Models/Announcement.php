<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'title',
        'content',
        'cover_image',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = ['cover_image_url'];

    /**
     * Get the full URL for the cover image
     */
    public function getCoverImageUrlAttribute()
    {
        if (!$this->cover_image) {
            return null;
        }
        
        // If it's already a full URL (external storage), return as-is
        if (filter_var($this->cover_image, FILTER_VALIDATE_URL)) {
            return $this->cover_image;
        }
        
        // Otherwise, prepend the APP_URL + /storage/ for local files
        return rtrim(config('app.url'), '/') . '/storage/' . $this->cover_image;
    }

    /**
     * Get the staff that created the announcement
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Alias for backward compatibility
     */
    public function user()
    {
        return $this->staff();
    }

    /**
     * Scope to get only published announcements
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    /**
     * Scope to get only draft announcements
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope to get only archived announcements
     */
    public function scopeArchive($query)
    {
        return $query->where('status', 'archive');
    }
}
