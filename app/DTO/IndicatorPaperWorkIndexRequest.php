<?php

namespace App\DTO;

class IndicatorPaperWorkIndexRequest
{
    public string $level;
    public ?string $unit = null;
    public string|int|null $year = null;
    public string|int $userId;
}
