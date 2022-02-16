<?php

namespace App\DTO;

class UnitStoreRequest
{
    public string $name;
    public string $level;
    public string $parent_unit;
    public string|int $userId;
}
