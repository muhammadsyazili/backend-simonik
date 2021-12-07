<?php

namespace App\DTO;

class IndicatorInsertRequenst {
    public ?array $validity = null;
    public ?array $weight = null;
    public bool $dummy = false;
    public ?bool $reducing_factor = null;
    public ?string $polarity = null;
    public string $indicator = '';
    public ?string $formula = null;
    public ?string $measure = null;
    public string $user_id = '';
}
