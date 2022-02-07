<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UnitRepository;
use Illuminate\Http\Request;

class UnitValidationService {
    private ?UnitRepository $unitRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->unitRepository = $constructRequest->unitRepository;
        }
    }

    public function indexValidation(Request $request)
    {

    }

    public function createValidation(Request $request)
    {

    }

    public function storeValidation(Request $request)
    {

    }

    public function editValidation(Request $request)
    {

    }

    public function updateValidation(Request $request)
    {

    }

    public function destroyValidation(Request $request)
    {

    }
}
