<?php

namespace App\DTO;

class LevelUpdateRequest
{
    public string|int $id;
    public string $name;
    public string $parentLevel;
}
