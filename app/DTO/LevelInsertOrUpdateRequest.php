<?php

namespace App\DTO;

class LevelInsertOrUpdateRequest
{
    public ?int $id = null;
    public string $name;
    public string $parent_level;
}
