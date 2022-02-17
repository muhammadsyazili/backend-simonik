<?php

namespace App\Domains;

class Indicator
{
    public string|int $id;
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
    public string|int|null $unit_id;
    public string|int $level_id;
    public int $order;
    public ?string $code;
    public string|int|null $parent_vertical_id;
    public string|int|null $parent_horizontal_id;
    public ?string $created_by;
}
