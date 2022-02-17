<?php

namespace App\DTO;

class UnitUpdateRequest
{
    public string|int $id;
    public string $name;
    public string $level;
    public ?string $parent_unit = null;
    public string|int $userId;
}
