<?php

namespace App\DTO;

class IndicatorReferenceUpdateRequest
{
    public array $indicators;
    public array $preferences;
    public string $level;
    public ?string $unit = null;
    public string|int|null $year = null;
    public string|int|null $userId = null;
}
