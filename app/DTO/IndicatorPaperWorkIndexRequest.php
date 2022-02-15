<?php

namespace App\DTO;

class IndicatorPaperWorkIndexRequest
{
    public string $level;
    public ?string $unit = null;
    public ?string $year = null;
    public string|int $userId;
}
