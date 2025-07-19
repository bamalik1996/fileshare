<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class SharedText extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['ip_address','content','expires_at'];
}
