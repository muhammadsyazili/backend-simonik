<?php

namespace App\Domains;

class Level
{
    public string|int $id;
    public string $name;
    public string $slug;
    public string|int|null $parent_id;
}
