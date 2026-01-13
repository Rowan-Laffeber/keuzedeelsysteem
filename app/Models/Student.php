<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'studentnummer', 'opleidingsnummer', 'cohort_year'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function inschrijvingen()
    {
        return $this->hasMany(Inschrijving::class);
    }
}
