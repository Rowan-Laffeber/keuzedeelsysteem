<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'user_id', 'studentnummer', 'opleidingsnummer', 'cohort_year', 'roostergroep'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function keuzedelen()
    {
        return $this->belongsToMany(Keuzedeel::class, 'keuzedeel_student')
            ->withTimestamps();
    }
}
