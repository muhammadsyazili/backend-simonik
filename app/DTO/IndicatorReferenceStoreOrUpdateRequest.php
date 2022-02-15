<?php

namespace App\DTO;

class IndicatorReferenceStoreOrUpdateRequest
{
    public array $indicators;
    public array $preferences;
    public ?string $level = null;
    public ?string $unit = null;
    public ?string $year = null;
    public string|int|null $userId;
}
