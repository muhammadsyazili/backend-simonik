<?php

namespace App\DTO;

class UnitUpdateRequest
{
    public string|int $id;
    public string $name;
    public string $level;
    public ?string $parentUnit = null;
    public string|int $userId;
}
