<?php

namespace App\DTO;

class IndicatorPaperWorkStoreRequest
{
    public array $indicators;
    public string $level;
    public string|int $year;
    public string|int $userId;
}
