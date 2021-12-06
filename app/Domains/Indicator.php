<?php

namespace App\Domains;

class Indicator {
    public string $id;
    public string $indicator;
    public ?string $formula;
    public ?string $measure;
    public ?string $weight;
    public ?string $polarity;
    public ?string $year;
    public ?bool $reducing_factor;
    public ?string $validity;
    public bool $reviewed;
    public bool $referenced;
    public bool $dummy;
    public string $label;
    public ?string $unit_id;
    public int $level_id;
    public int $order;
    public ?string $code;
    public ?string $parent_vertical_id;
    public ?string $parent_horizontal_id;
    public ?string $created_by;
}
