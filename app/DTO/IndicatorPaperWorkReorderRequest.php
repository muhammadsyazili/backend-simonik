<?php

namespace App\DTO;

class IndicatorPaperWorkReorderRequest
{
    public array $indicators;
    public string $level;
    public ?string $unit = null;
    public ?string $year = null;
}
