<?php

namespace App\DTO;

class LevelStoreOrUpdateRequest
{
    public string|int|null $id = null;
    public string $name;
    public string $parent_level;
}
