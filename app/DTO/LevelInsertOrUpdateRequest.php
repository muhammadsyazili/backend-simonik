<?php

namespace App\DTO;

class LevelInsertOrUpdateRequest
{
    public string|int|null $id = null;
    public string $name;
    public string $parent_level;
}
