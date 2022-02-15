<?php

namespace App\DTO;

class IndicatorPaperWorkReorderRequest
{
    public array $indicators;
    public string $level;
    public ?string $unit;
    public ?string $year;
}
