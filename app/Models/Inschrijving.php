<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inschrijving extends Model
{
    protected $table = 'inschrijvingen';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'student_id',
        'eerste_keuze_keuzedeel_id',
        'tweede_keuze_keuzedeel_id',
        'toegewezen_keuzedeel_id',
        'status',
        'afgerond',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function eersteKeuze()
    {
        return $this->belongsTo(Keuzedeel::class, 'eerste_keuze_keuzedeel_id');
    }

    public function tweedeKeuze()
    {
        return $this->belongsTo(Keuzedeel::class, 'tweede_keuze_keuzedeel_id');
    }
}
