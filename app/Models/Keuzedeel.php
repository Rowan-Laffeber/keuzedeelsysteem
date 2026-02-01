<?php

namespace App\Models;

use App\Models\Student;
use Illuminate\Database\Eloquent\Model;
use App\Support\StatusHelper;
use App\Models\Inschrijving;

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
        'minimum_studenten',
        'maximum_studenten',
        'parent_max_type', // for setting if the max 30 students is over the parent or subdeel
        'start_inschrijving',
        'eind_inschrijving',
    ];

    protected $appends = ['status_helper'];

    protected $casts = [
        'start_inschrijving' => 'datetime',
        'eind_inschrijving' => 'datetime',
    ];

    /**
     * Status helper accessor
     */
    public function getStatusHelperAttribute(): StatusHelper
    {
        return new \App\Support\StatusHelper($this);
    }





    /**
     * Get actual enrollment count from inschrijvingen table
     */
    public function getIngeschrevenCountAttribute(): int
    {
        // Ensure we get fresh data, not cached
        return $this->bevestigdeStudenten()->count();
    }

    /**
     * Refresh enrollment count cache
     */
    public function refreshEnrollmentCount(): void
    {
        $this->load('bevestigdeStudenten');
        unset($this->ingeschreven_count);
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

    public function students()
    {
        return $this->belongsToMany(Student::class, 'inschrijvings')
            ->withPivot(['status', 'opmerkingen', 'inschrijfdatum'])
            ->withTimestamps()
            ->withCasts([
                'inschrijfdatum' => 'datetime',
            ]);
    }

    public function inschrijvingen()
    {
        return $this->hasMany(Inschrijving::class);
    }

    public function bevestigdeStudenten()
    {
        return $this->belongsToMany(Student::class, 'inschrijvings')
            ->wherePivot('status', 'goedgekeurd')
            ->withPivot(['status', 'opmerkingen', 'inschrijfdatum'])
            ->withTimestamps()
            ->withCasts([
                'inschrijfdatum' => 'datetime',
            ]);
    }

    /**
     * Check if a given priority is full (maximum students reached)
     */
    public function isPrioFull(int $priority): bool
    {
        $max = $this->maximum_studenten ?? $this->parent?->maximum_studenten ?? null;
        if (!$max) return false;

        $count = $this->inschrijvingen()
            ->where('priority', $priority)
            ->count();

        return $count >= $max;
    }

    public function totaalGoedgekeurdVanSubdelen(): int
    {
        return $this->delen()->with('inschrijvingen')
            ->get()
            ->sum(fn($deel) => $deel->goedgekeurdeInschrijvingenCount());
    }

    public function goedgekeurdeInschrijvingenCount(): int
    {
        return $this->inschrijvingen()->where('status', 'goedgekeurd')->count();
    }

}

    
    // /**
    //  * NIET NODIG, ELKE STUDENT MAG ELK KEUZEDEEL DOEN
    //  * Scope om keuzedelen te filteren op basis van opleidingsnummer prefix van de student. 
    //  *
    //  * @param \Illuminate\Database\Eloquent\Builder $query
    //  * @param string|null $opleidingPrefix Eerste 5 karakters van opleidingsnummer
    //  * @return \Illuminate\Database\Eloquent\Builder
    //  */
    // public function scopeForStudentOpleiding($query, ?string $opleidingPrefix)
    // {
    // // Als geen prefix is gegeven, geef alles terug
    //     if (!$opleidingPrefix) {
    //         return $query;
    //     }
        
    //     return $query->where(function ($q) use ($opleidingPrefix) {
    //         // Child keuzedelen met ID beginnend met prefix + K
    //         $q->where('id', 'like', $opleidingPrefix . 'K%')
    //             // Of kind ID beginnend met alleen K (voor iedereen)
    //             ->orWhere('id', 'like', 'K%');
    //     });
    // }
    

