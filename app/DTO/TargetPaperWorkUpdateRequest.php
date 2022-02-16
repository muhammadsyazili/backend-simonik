<?php

namespace App\DTO;

class TargetPaperWorkUpdateRequest
{
    public string|int $userId;
    public array $indicators;
    public array $targets;
    public string $level;
    public string $unit;
    public string $year;
}
