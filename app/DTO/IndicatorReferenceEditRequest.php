<?php

namespace App\DTO;

class IndicatorReferenceEditRequest
{
    public string $level;
    public ?string $unit = null;
    public string|int|null $year = null;
}
