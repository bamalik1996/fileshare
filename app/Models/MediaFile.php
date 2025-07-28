<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;


class MediaFile extends Model implements HasMedia
{

    use InteractsWithMedia;

    protected $fillable = [
        'file_name',
        'original_name',
        'mime_type',
        'storage_path',
        'ip_address',
        'file_size',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'file_size' => 'integer',
    ];


    /**
     * Scope to get non-expired files
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope to get files by IP address
     */
    public function scopeByIp($query, $ipAddress)
    {
        return $query->where('ip_address', $ipAddress);
    }

    /**
     * Scope to get active files (not expired and by IP)
     */
    public function scopeActive($query, $ipAddress)
    {
        return $query->byIp($ipAddress)->notExpired();
    }

    /**
     * Get the full file path
     */
    public function getFullPathAttribute()
    {
        return Storage::disk('public')->path($this->storage_path);
    }

    /**
     * Check if file exists on disk
     */
    public function existsOnDisk()
    {
        return Storage::disk('public')->exists($this->storage_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    /**
     * Check if file is an image
     */
    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    /**
     * Get file extension
     */
    public function getExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }

    /**
     * Clean up expired files (can be called via scheduled command)
     */
    public static function cleanupExpired()
    {
        $expiredFiles = static::where('expires_at', '<', Carbon::now())->get();

        foreach ($expiredFiles as $file) {
            $file->delete(); // This will also delete the physical file
        }

        return $expiredFiles->count();
    }
}
