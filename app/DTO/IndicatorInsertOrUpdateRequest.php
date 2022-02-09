<?php

namespace App\DTO;

class IndicatorInsertOrUpdateRequest {
    public ?string $id = null;
    public ?array $validity = null;
    public ?array $weight = null;
    public string $dummy;
    public ?string $reducing_factor = null;
    public ?string $polarity = null;
    public string $indicator;
    public ?string $formula = null;
    public ?string $measure = null;
    public string $user_id;
}
