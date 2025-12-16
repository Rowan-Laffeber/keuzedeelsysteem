<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasFactory;

    // Table name is "posts" by default, so no need to specify it.

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'body',
    ];
}