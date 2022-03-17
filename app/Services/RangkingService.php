<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\DTO\RangkingRangkingRequest;
use App\DTO\RangkingRangkingResponse;

class RangkingService
{
    public function __construct(ConstructRequest $constructRequest)
    {
        $this->levelRepository = $constructRequest->levelRepository;
    }

    public function rangking(RangkingRangkingRequest $rangkingRequest): RangkingRangkingResponse
    {
        $response = new RangkingRangkingResponse();

        $id = $rangkingRequest->id;
        $year = $rangkingRequest->year;
        $month = $rangkingRequest->month;

        return $response;
    }
}
