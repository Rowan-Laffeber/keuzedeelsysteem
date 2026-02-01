<?php

namespace App\Support;

use App\Models\Keuzedeel;

class StatusHelper
{
    public string $status; // Tile status (achtergrond)
    public int $ingeschreven = 0;
    public int $min = 0;
    public int $max = 0;

    public function __construct(Keuzedeel $keuzedeel)
    {
        // Inactief als keuzedeel zelf of alle subdelen inactief zijn
        if (!$keuzedeel->actief || ($keuzedeel->delen()->count() > 0 && $keuzedeel->delen()->where('actief', true)->count() === 0)) {
            $this->status = 'inactief';
            $this->ingeschreven = 0;
            return;
        }

        // Bereken ingeschreven/min/max (alleen afgeronde/goedgekeurde inschrijvingen tellen)
        $this->ingeschreven = $keuzedeel->goedgekeurdeInschrijvingenCount();
        $this->min = $keuzedeel->minimum_studenten ?? 0;
        $this->max = $keuzedeel->maximum_studenten ?? 0;

        if ($this->ingeschreven < $this->min) {
            $this->status = 'niet_genoeg';
        } elseif ($this->max !== 0 && $this->ingeschreven >= $this->max) {
            $this->status = 'geen_plek';
        } else {
            $this->status = 'nog_plek';
        }
    }

    // Tile achtergrondkleur
    public function color(): string
    {
        return match ($this->status) {
            'inactief'    => 'bg-gray-300',
            'nog_plek'    => 'bg-blue-300',
            'niet_genoeg' => 'bg-orange-300',
            'geen_plek'   => 'bg-red-300',
            default       => 'bg-gray-300',
        };
    }

    // Badge tekst
    public function text(): string
    {
        return match ($this->status) {
            'inactief'    => 'Keuzedeel niet actief',
            'nog_plek'    => "{$this->ingeschreven} ingeschreven",
            'niet_genoeg' => 'Niet genoeg inschrijvingen',
            'geen_plek'   => 'Keuzedeel vol',
            default       => 'Onbekend',
        };
    }
}
