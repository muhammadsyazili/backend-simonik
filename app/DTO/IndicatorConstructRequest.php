<?php

namespace App\DTO;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;

class IndicatorConstructRequest {
    public ?IndicatorRepository $indicatorRepository = null;
    public ?LevelRepository $levelRepository = null;
    public ?UnitRepository $unitRepository = null;
}
