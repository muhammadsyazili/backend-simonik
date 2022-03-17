<?php

namespace App\DTO;

class IndicatorStoreRequest
{
    public ?array $validity = null;
    public ?array $weight = null;
    public string $dummy;
    public string $type;
    public ?string $reducingFactor = null;
    public ?string $polarity = null;
    public string $indicator;
    public ?string $formula = null;
    public ?string $measure = null;
    public string|int $userId;
}
