<?php

namespace App\DTO;

class UnitStoreRequest
{
    public string $name;
    public string $level;
    public ?string $parent_unit = null;
    public string|int $userId;
}
