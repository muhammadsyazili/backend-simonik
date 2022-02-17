<?php

namespace App\Domains;

class Unit
{
    public string|int $id;
    public string $name;
    public string $slug;
    public string|int|null $parent_id;
    public string|int|null $level_id;
}
