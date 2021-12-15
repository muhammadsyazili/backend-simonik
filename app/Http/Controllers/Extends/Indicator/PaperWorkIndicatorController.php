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
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();

        $constructRequenst = new ConstructRequest();

        $constructRequenst->userRepository = $userRepository;
        $constructRequenst->indicatorRepository = $indicatorRepository;
        $constructRequenst->levelRepository = $levelRepository;
        $constructRequenst->unitRepository = $unitRepository;

        $IndicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequenst);

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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequenst);

        $user_id = $request->header('X-User-Id');
        $level = $request->query('level');
        $unit = $request->query('unit');
        $year = $request->query('tahun');

        $response = $indicatorPaperWorkService->index($user_id, $level, $unit, $year);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work indicator 'level: %s' 'unit: %s' 'year: %s' showed", $level, $unit, $year),
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
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $userRepository = new UserRepository();
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();

        $constructRequenst = new ConstructRequest();

        $constructRequenst->userRepository = $userRepository;
        $constructRequenst->indicatorRepository = $indicatorRepository;
        $constructRequenst->levelRepository = $levelRepository;

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequenst);

        $response = $indicatorPaperWorkService->create(
            $request->header('X-User-Id')
        );

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Paper work indicators showed",
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
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $userRepository = new UserRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequenst = new ConstructRequest();

        $constructRequenst->indicatorRepository = $indicatorRepository;
        $constructRequenst->levelRepository = $levelRepository;
        $constructRequenst->unitRepository = $unitRepository;
        $constructRequenst->userRepository = $userRepository;
        $constructRequenst->targetRepository = $targetRepository;
        $constructRequenst->realizationRepository = $realizationRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequenst);

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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequenst);

        $indicatorPaperWorkService->store(
            $request->post('indicators'),
            $request->post('level'),
            $request->post('year'),
            $request->header('X-User-Id')
        );

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work indicator 'level: %s' 'year: %s' creating successfully.", $request->post('level'), $request->post('year')),
            null,
            null,
        );
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($level, $unit, $year)
    {
        $indicatorRepository = new IndicatorRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $targetRepository = new TargetRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequenst = new ConstructRequest();

        $constructRequenst->indicatorRepository = $indicatorRepository;
        $constructRequenst->levelRepository = $levelRepository;
        $constructRequenst->unitRepository = $unitRepository;
        $constructRequenst->targetRepository = $targetRepository;
        $constructRequenst->realizationRepository = $realizationRepository;

        $indicatorPaperWorkValidationService = new IndicatorPaperWorkValidationService($constructRequenst);

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

        $indicatorPaperWorkService = new IndicatorPaperWorkService($constructRequenst);

        $indicatorPaperWorkService->destroy($level, $unit, $year);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Paper work indicator 'level: %s' 'unit: %s' 'year: %s' deleting successfully.", $level, $unit, $year),
            null,
            null,
        );
    }
}
