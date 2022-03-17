<?php

namespace App\DTO;

class IndicatorReferenceStoreRequest
{
    public array $indicators;
    public array $preferences;
    public string|int|null $userId = null;
}
