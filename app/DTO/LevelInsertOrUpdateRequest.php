<?php

namespace App\DTO;

class LevelInsertOrUpdateRequest {
    public ?string $id = null;
    public string $name;
    public string $parent_level;
}
