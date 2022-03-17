<?php

namespace App\DTO;

class IndicatorPaperWorkUpdateRequest
{
    public array $indicators;
    public string $level;
    public string $unit;
    public string|int $year;
    public string|int $userId;
}
