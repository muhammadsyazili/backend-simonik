<?php

namespace App\DTO;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;

class IndicatorConstructRequenst {
    public IndicatorRepository $indicatorRepository;
    public ?LevelRepository $levelRepository = null;
}
