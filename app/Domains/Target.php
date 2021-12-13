<?php

namespace App\Domains;

class Target {
    public string $id;
    public string $indicator_id;
    public string $month;
    public float $value;
    public bool $locked;
    public bool $default;
}
