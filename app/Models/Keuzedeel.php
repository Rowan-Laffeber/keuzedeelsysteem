<?php

namespace App\Models;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use App\Support\StatusHelper;

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
        'parent_max_type', // for setting if the max 30 students is over the parent or subdeel
        'start_inschrijving',
        'eind_inschrijving',
    ];

    protected $appends = ['status_helper'];

    /**
     * Status helper accessor
     */
    public function getStatusHelperAttribute(): StatusHelper
    {
        return new StatusHelper(
            $this->is_open ? 'nog_plek' : 'afgerond',
            $this->maximum_studenten ?? 0,
            $this->ingeschreven ?? 0
        );
    }

    /**
     * Child keuzedelen (delen)
     */
    public function delen()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('volgorde');
    }

    /**
     * Parent keuzedeel
     */
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function keuzedelen()
    {
        return $this->belongsToMany(Keuzedeel::class);
    }

}
