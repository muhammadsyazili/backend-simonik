<?php

namespace App\DTO;

class UnitStoreOrUpdateRequest
{
    public string|int|null $id = null;
    public string $name;
    public string $level;
    public string $parent_unit;
    public string|int|null $userId;
}
