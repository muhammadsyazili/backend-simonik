<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RangkingRangkingRequest;
use App\DTO\RangkingRangkingResponse;
use App\Repositories\LevelRepository;

class RangkingService
{
    private ?LevelRepository $levelRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
    }

    //use repo LevelRepository
    public function rangking(RangkingRangkingRequest $rangkingRequest): RangkingRangkingResponse
    {
        $response = new RangkingRangkingResponse();

        $parentId = $rangkingRequest->parentId;
        $year = $rangkingRequest->year;
        $month = $rangkingRequest->month;

        $this->levelRepository->find__all__by__parentId($parentId);

        return $response;
    }
}
