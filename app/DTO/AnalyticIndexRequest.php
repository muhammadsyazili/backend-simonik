<?php

namespace App\DTO;

class AnalyticIndexRequest
{
    public string|int $userId;
    public string $level;
    public string $unit;
    public int $year;
    public int $month;
}
