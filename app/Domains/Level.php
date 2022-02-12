<?php

namespace App\Domains;

class Level
{
    public string|int|null $id;
    public string $name;
    public string $slug;
    public string|int|null $parent_id;
}
