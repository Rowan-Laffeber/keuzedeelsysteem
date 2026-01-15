<?php

namespace App\Models;

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
        'parent_max_type',
        'start_inschrijving',
        'eind_inschrijving',
    ];

    protected $appends = ['status_helper'];

    public function getStatusHelperAttribute(): StatusHelper
    {
        return new StatusHelper(
            $this->is_open ? 'nog_plek' : 'afgerond',
            $this->maximum_studenten ?? 0,
            $this->ingeschreven ?? 0
        );
    }

    public function delen()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('volgorde');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    // filtering keuzedelen based on student
    public function isEligibleForStudent($student)
    {
        if (!$student || $student->user->role !== 'student') {
            return true;
        }

        if (!$this->parent_id) { // only subdelen have parent_id
            return true;
        }

        preg_match('/^(\d+)?K/', $this->id, $matches);
        $keuzedeelNumber = $matches[1] ?? null;

        preg_match('/^(\d+)(?:bol|bbl)/i', $student->opleidingsnummer, $matches);
        $opleidingNumber = $matches[1] ?? null;

        // If no number before K, all students can see
        if (!$keuzedeelNumber) {
            return true;
        }

        return $keuzedeelNumber === $opleidingNumber;
    }


}
