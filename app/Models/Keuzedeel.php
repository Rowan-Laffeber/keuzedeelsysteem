<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Keuzedeel extends Model
{
    protected $table = 'keuzedelen';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'title',
        'description',
        'parent_id',
        'volgorde',
        'actief',
        'is_open',
        'minimum_studenten',
        'maximum_studenten',
        'start_inschrijving',
        'eind_inschrijving',
    ];

    public function delen()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('volgorde');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
