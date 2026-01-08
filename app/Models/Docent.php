<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Docent extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'user_id', 'afkorting'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function keuzedelen()
    {
        return $this->hasMany(Keuzedeel::class);
    }
}
