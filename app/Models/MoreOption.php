<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MoreOption extends Model
{
    use HasFactory;

    protected $table = 'more_options';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'student_id',
        'keuzedeel_id',
        'priority', // 1 = first choice, 2 = second choice, 3 = third choice
        'status', // 'pending', 'assigned', 'rejected'
    ];

    protected $casts = [
        'priority' => 'integer',
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
    public function scopeFirstChoice($query)
    {
        return $query->where('priority', 1);
    }

    public function scopeSecondChoice($query)
    {
        return $query->where('priority', 2);
    }

    public function scopeThirdChoice($query)
    {
        return $query->where('priority', 3);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAssigned($query)
    {
        return $query->where('status', 'assigned');
    }
}
