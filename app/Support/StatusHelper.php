<?php

namespace App\Support;

class StatusHelper
{
    public string $status;
    public int $max;
    public int $ingeschreven;

    public const MINIMUM_INSCHRIJVINGEN = 15;

    public function __construct(string $status, int $max = 0, int $ingeschreven = 0)
    {
        $this->max = $max;
        $this->ingeschreven = $ingeschreven;

        // if ($this->ingeschreven >= $this->max) {
        //     $this->status = 'geen_plek';
        // } else
        if ($status === 'nog_plek' && $this->ingeschreven < self::MINIMUM_INSCHRIJVINGEN) {
            $this->status = 'niet_genoeg';
        } else {
            $this->status = $status;
        }
    }

    public function color(): string
    {
        return match ($this->status) {
            'nog_plek' => 'bg-blue-300',
            'niet_genoeg' => 'bg-orange-300',
            'afgerond' => 'bg-green-300',
            'keuze1' => 'bg-yellow-300',
            'keuze2' => 'bg-yellow-200',
            'geen_plek' => 'bg-red-300',
            default => 'bg-gray-300',
        };
    }

    public function textColor(): string
    {
        return match ($this->status) {
            'nog_plek' => 'text-blue-900',
            'niet_genoeg' => 'text-orange-900',
            'afgerond' => 'text-green-900',
            'keuze1' => 'text-yellow-900',
            'keuze2' => 'text-yellow-800',
            'geen_plek' => 'text-red-900',
            default => 'text-gray-900',
        };
    }

    public function text(): string
    {
        return match ($this->status) {
            'nog_plek' => 'Nog ' . max(0, $this->max - $this->ingeschreven) . ' plaatsen',
            'niet_genoeg' => 'Niet genoeg inschrijvingen!',
            'afgerond' => 'Afgerond',
            'keuze1' => '1e keus',
            'keuze2' => '2e keus',
            'geen_plek' => 'Geen plaats',
            default => 'Onbekend',
        };
    }
}
