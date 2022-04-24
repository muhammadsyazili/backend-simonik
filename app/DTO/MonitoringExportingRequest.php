<?php

namespace App\DTO;

class MonitoringExportingRequest
{
    public string $level;
    public string $unit;
    public string|int $year;
    public string $month;
}
