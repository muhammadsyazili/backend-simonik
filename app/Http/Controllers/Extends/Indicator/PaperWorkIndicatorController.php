<?php

namespace App\Http\Controllers\Extends\Indicator;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\ApiController;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorPaperWorkCreateRequest;
use App\DTO\IndicatorPaperWorkDestroyRequest;
use App\DTO\IndicatorPaperWorkEditRequest;
use App\DTO\IndicatorPaperWorkIndexRequest;
use App\DTO\IndicatorPaperWorkReorderRequest;
use App\DTO\IndicatorPaperWorkStoreRequest;
use App\DTO\IndicatorPaperWorkUpdateRequest;
use App\DTO\PublicIndicatorsRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\IndicatorPaperWorkService;
use App\Services\IndicatorPaperWorkValidationService;

class PaperWorkIndicatorController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $IndicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequest);

        $validation = $IndicatorPaperWorkValidationService->indexValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorPaperWorkIndexRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $response = $indicatorPaperWorkService->index($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Indikator (Level: %s) (Unit: %s) (Tahun: %s)", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            [
                'indicators' => $response->indicators,
                'permissions' => $response->permissions,
            ],
            null,
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;

        $requestDTO = new IndicatorPaperWorkCreateRequest();

        $requestDTO->userId = $request->header('X-User-Id');

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $response = $indicatorPaperWorkService->create($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja Indikator",
            [
                'indicators' => $response->indicators,
                'levels' => $response->levels,
            ],
            null,
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequest);

        $validation = $indicatorPaperWorkValidationService->storeValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorPaperWorkStoreRequest();

        $requestDTO->indicators = $request->post('indicators');
        $requestDTO->level = $request->post('level');
        $requestDTO->year = $request->post('year');
        $requestDTO->userId = $request->header('X-User-Id');

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->store($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Indikator (Level: %s) (Tahun: %s) Berhasil Dibuat", strtoupper($requestDTO->level), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $level
     * @param  string  $unit
     * @param  string  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request, $level, $unit, $year)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequest);

        $userId = $request->header('X-User-Id');

        $validation = $indicatorPaperWorkValidationService->editValidation($userId, $level, $unit, $year);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorPaperWorkEditRequest();

        $requestDTO->level = $level;
        $requestDTO->unit = $unit;
        $requestDTO->year = $year;

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $response = $indicatorPaperWorkService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja Indikator",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $level
     * @param  string  $unit
     * @param  string  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $level, $unit, $year)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequest);

        $validation = $indicatorPaperWorkValidationService->updateValidation($request, $level, $unit, $year);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorPaperWorkUpdateRequest();

        $requestDTO->indicators = $request->post('indicators');
        $requestDTO->level = $level;
        $requestDTO->unit = $unit;
        $requestDTO->year = $year;
        $requestDTO->userId = $request->header('X-User-Id');

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->update($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Indikator (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Diubah", strtoupper($level), strtoupper($unit), strtoupper($year)),
            null,
            null,
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $level
     * @param  string  $unit
     * @param  string  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $level, $unit, $year)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequest);

        $userId = $request->header('X-User-Id');

        $validation = $indicatorPaperWorkValidationService->destroyValidation($userId, $level, $unit, $year);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorPaperWorkDestroyRequest();

        $requestDTO->level = $level;
        $requestDTO->unit = $unit;
        $requestDTO->year = $year;

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->destroy($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Indikator (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Dihapus", strtoupper($level), strtoupper($unit), strtoupper($year)),
            null,
            null,
        );
    }

    /**
     * Reorder Indicator.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequest);

        $validation = $indicatorPaperWorkValidationService->reorderValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new IndicatorPaperWorkReorderRequest();

        $requestDTO->indicators = $request->post('indicators');
        $requestDTO->level = $request->post('level');
        $requestDTO->unit = $request->post('unit');
        $requestDTO->year = $request->post('year');

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->reorder($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            $requestDTO->level === 'super-master' ? sprintf("Kertas Kerja Indikator (Level: %s) Berhasil Diurutkan Ulang", strtoupper($requestDTO->level)) : sprintf("Kertas Kerja Indikator (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Diurutkan Ulang", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }

    /**
     * public indicators.
     *
     * @param  string  $level
     * @param  string  $unit
     * @param  string  $year
     * @return \Illuminate\Http\JsonResponse
     */
    public function public_indicators($level, $unit, $year)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $IndicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService();

        $validation = $IndicatorPaperWorkValidationService->publicIndicatorsValidation($level, $unit, $year);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new PublicIndicatorsRequest();

        $requestDTO->level = $level;
        $requestDTO->unit = $unit;
        $requestDTO->year = $year;

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $response = $indicatorPaperWorkService->public_indicators($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Daftar Indikator (Level: %s) (Unit: %s) (Tahun: %s)", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            $response->indicators,
            null,
        );
    }
}
