<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use Illuminate\Http\Request;

class LevelValidationService {
    private ?LevelRepository $levelRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->levelRepository = $constructRequest->levelRepository;
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
