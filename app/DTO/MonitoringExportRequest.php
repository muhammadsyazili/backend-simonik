<?php

namespace App\DTO;

class MonitoringExportRequest
{
    public string $level;
    public string $unit;
    public string|int $year;
    public string $month;
}
