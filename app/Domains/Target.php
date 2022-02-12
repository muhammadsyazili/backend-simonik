<?php

namespace App\Domains;

class Target
{
    public string|int $id;
    public string|int $indicator_id;
    public string $month;
    public float $value;
    public bool $locked;
    public bool $default;
}
