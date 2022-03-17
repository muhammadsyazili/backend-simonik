<?php

namespace App\DTO;

class RealizationPaperWorkUpdateRequest
{
    public string|int $userId;
    public array $indicators;
    public array $realizations;
    public string $level;
    public string $unit;
    public string|int $year;
}
