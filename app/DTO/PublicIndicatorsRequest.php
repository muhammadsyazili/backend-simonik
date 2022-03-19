<?php

namespace App\DTO;

class PublicIndicatorsRequest
{
    public string $level;
    public ?string $unit = null;
    public string|int|null $year = null;
}
