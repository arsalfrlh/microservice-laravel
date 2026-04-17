<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $table = "posts";
    protected $fillable = ['user_id','caption','video_path','subtitle','embedding'];

    function user(){
        return $this->belongsTo(User::class,'user_id');
    }

    function likedBy(){
        return $this->belongsToMany(User::class,'likes','post_id','user_id');
    }

    function like(){
        return $this->hasMany(Like::class,'post_id');
    }

    function view(){
        return $this->hasMany(View::class,'post_id');
    }
}
