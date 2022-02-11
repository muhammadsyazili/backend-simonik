<?php

namespace App\Domains;

class Unit
{
    public ?string $id;
    public string $name;
    public string $slug;
    public ?string $parent_id;
    public ?int $level_id;
}
