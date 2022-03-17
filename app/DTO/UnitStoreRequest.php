<?php

namespace App\DTO;

class UnitStoreRequest
{
    public string $name;
    public string $level;
    public ?string $parentUnit = null;
    public string|int $userId;
}
