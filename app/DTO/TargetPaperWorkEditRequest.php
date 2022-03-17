<?php

namespace App\DTO;

class TargetPaperWorkEditRequest
{
    public string $level;
    public string $unit;
    public string|int $year;
    public string|int $userId;
}
