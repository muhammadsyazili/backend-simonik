<?php

namespace App\DTO;

class UnitInsertOrUpdateRequest
{
    public string|int|null $id = null;
    public string $name;
    public string $parent_level;
    public string $parent_unit;
    public string|int|null $userId;
}
