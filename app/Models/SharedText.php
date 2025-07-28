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
    
   
}
