<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;

class UnitService
{

    private ?UnitRepository $unitRepository;
    private ?LevelRepository $levelRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->unitRepository = $constructRequest->unitRepository;
        $this->levelRepository = $constructRequest->levelRepository;
    }

    public function index()
    {
        return $this->unitRepository->find__all__with__level_parent();
    }

    //use repo UnitRepository, LevelRepository
    public function unitsOfLevel(string $level)
    {
        return $this->unitRepository->find__allSlug_allName__by__levelId($this->levelRepository->find__id__by__slug($level));
    }
}
