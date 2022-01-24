<?php

namespace App\Http\Controllers\Extends\Indicator;

use App\DTO\ConstructRequest;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $user_id = $request->header('X-User-Id');
        $level = $request->query('level');
        $unit = $request->query('unit');
        $year = $request->query('tahun');

        $response = $indicatorPaperWorkService->index($user_id, $level, $unit, $year);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) ditampilkan !", strtoupper($level), strtoupper($unit), strtoupper($year)),
            [
                'levels' => $response->levels,
                'indicators' => $response->indicators,
                'permissions' => $response->permissions,
            ],
            null,
        );
    }

    /**
     * Show the form for creating a new resource.
     *
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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $response = $indicatorPaperWorkService->create(
            $request->header('X-User-Id')
        );

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI ditampilkan !",
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
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $userRepository = new UserRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->userRepository = $userRepository;
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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->store(
            $request->post('indicators'),
            $request->post('level'),
            $request->post('year'),
            $request->header('X-User-Id')
        );

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas kerja KPI (Level: %s) (Tahun: %s) berhasil dibuat !", strtoupper($request->post('level')), strtoupper($request->post('year'))),
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
    public function edit($level, $unit, $year)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService();

        $validation = $indicatorPaperWorkValidationService->editValidation($level, $unit, $year);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $response = $indicatorPaperWorkService->edit($level, $unit, $year);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas kerja KPI ditampilkan !",
            [
                'super_master_indicators' => $response->super_master_indicators,
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
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->update($request->post('indicators'), $level, $unit, $year, $request->header('X-User-Id'));

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) berhasil diubah !", strtoupper($level), strtoupper($unit), strtoupper($year)),
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
    public function destroy($level, $unit, $year)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->targetRepository = $targetRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService();

        $validation = $indicatorPaperWorkValidationService->destroyValidation($level, $unit, $year);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->destroy($level, $unit, $year);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) berhasil dihapus !", strtoupper($level), strtoupper($unit), strtoupper($year)),
            null,
            null,
        );
    }

    /**
     * Reorder the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reorder(Request $request)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequest = new ConstructRequest();

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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

        $indicatorPaperWorkService->reorder($request->post('indicators'), $request->post('level'), $request->post('unit'), $request->post('year'));

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            $request->post('level') === 'super-master' ? sprintf("Kertas kerja KPI (Level: %s) berhasil diurutkan ulang !", strtoupper($request->post('level'))) : sprintf("Kertas kerja KPI (Level: %s) (Unit: %s) (Tahun: %s) berhasil diurutkan ulang !", strtoupper($request->post('level')), strtoupper($request->post('unit')), strtoupper($request->post('year'))),
            null,
            null,
        );
    }
}
