<?php

namespace App\DTO;

class IndicatorPaperWorkDestroyRequest
{
    public string $level;
    public string $unit;
    public string|int $year;
    public string|int $userId;
}
