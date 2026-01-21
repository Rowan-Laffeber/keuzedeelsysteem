<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Inschrijving extends Model
{
    use HasFactory;

    protected $table = 'inschrijvings';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'student_id',
        'keuzedeel_id',
        'status',
        'opmerkingen',
        'inschrijfdatum',
    ];

    protected $casts = [
        'inschrijfdatum' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function keuzedeel()
    {
        return $this->belongsTo(Keuzedeel::class);
    }

    // Scopes for easy querying
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'goedgekeurd');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'ingediend');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'afgewezen');
    }
    public function scopeCompleted($query)
    {
        return $query->where('status', 'afgerond');
    }

    // Helper methods
    public function isConfirmed(): bool
    {
        return $this->status === 'goedgekeurd';
    }

    public function isPending(): bool
    {
        return $this->status === 'ingediend';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'afgewezen';
    }
    
    public function isCompleted(): bool
    {
        return $this->status === 'afgerond';
    }

    public function confirm(): void
    {
        $this->status = 'goedgekeurd';
        $this->save();
    }

    public function cancel(): void
    {
        $this->status = 'afgewezen';
        $this->save();
    }
}
