<?php

namespace App\DTO;

use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\RoleRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;

class ConstructRequest
{
    public ?IndicatorRepository $indicatorRepository = null;
    public ?LevelRepository $levelRepository = null;
    public ?UnitRepository $unitRepository = null;
    public ?UserRepository $userRepository = null;
    public ?RoleRepository $roleRepository = null;
    public ?TargetRepository $targetRepository = null;
    public ?RealizationRepository $realizationRepository = null;
}
