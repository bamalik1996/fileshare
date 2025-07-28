<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class SharedText extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['ip_address','content','expires_at'];
    
    protected $casts = [
        'expires_at' => 'datetime',
        'last_accessed' => 'datetime',
    ];
    
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('shared_files')
            ->acceptsMimeTypes([
                'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
                'application/pdf', 'text/plain', 'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/zip', 'application/x-rar-compressed'
            ])
            ->singleFile(false);
    }
    
    public function getMedia(string $collectionName = '', array $filters = []): \Illuminate\Support\Collection
    {
        $collection = $collectionName ?: 'shared_files';
        return parent::getMedia($collection, $filters);
    }
}
