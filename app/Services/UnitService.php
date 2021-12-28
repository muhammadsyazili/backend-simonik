<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;

class UnitService {

    private ?UnitRepository $unitRepository;
    private ?LevelRepository $levelRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->unitRepository = $constructRequest->unitRepository;
        $this->levelRepository = $constructRequest->levelRepository;
    }

    public function unitsOfLevel(string $level)
    {
        return $this->unitRepository->findAllSlugNameByLevelId($this->levelRepository->findIdBySlug($level));
    }
}
