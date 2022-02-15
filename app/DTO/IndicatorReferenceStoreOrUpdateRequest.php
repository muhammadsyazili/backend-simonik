<?php

namespace App\DTO;

class IndicatorReferenceStoreOrUpdateRequest
{
    public ?array $indicators;
    public ?array $preferences;
    public string|int|null $userId;
}
