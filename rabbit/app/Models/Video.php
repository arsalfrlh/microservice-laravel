<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    protected $table = "videos";
    protected $fillable = ['video_raw_path','video_hls_path'];
}
