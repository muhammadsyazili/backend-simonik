<?php

namespace App\Domains;

class Level {
    public ?int $id;
    public string $name;
    public string $slug;
    public ?int $parent_id;
}
