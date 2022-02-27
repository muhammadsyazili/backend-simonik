<?php

namespace App\Http\Controllers\Extends\Realization;

use App\DTO\ConstructRequest;
use App\DTO\RealizationPaperWorkChangeLockRequest;
use App\DTO\RealizationPaperWorkEditRequest;
use App\DTO\RealizationPaperWorkUpdateRequest;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\RealizationPaperWorkService;
use App\Services\RealizationPaperWorkValidationService;

class PaperWorkRealizationController extends ApiController
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(Request $request)
    {
        $userRepository = new UserRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;

        $realizationPaperWorkValidationService = new RealizationPaperWorkValidationService($constructRequest);

        $validation = $realizationPaperWorkValidationService->editValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new RealizationPaperWorkEditRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $realizationPaperWorkService = new RealizationPaperWorkService($constructRequest);

        $response = $realizationPaperWorkService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja Realisasi",
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
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        //logging
        // $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        // $output->writeln(sprintf('realizations: %s', json_encode($request->post('realizations'))));

        $userRepository = new UserRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $realizationPaperWorkValidationService = new RealizationPaperWorkValidationService($constructRequest);

        $validation = $realizationPaperWorkValidationService->updateValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new RealizationPaperWorkUpdateRequest();

        $requestDTO->indicators = array_keys($request->post('realizations'));
        $requestDTO->realizations = $request->post('realizations');
        $requestDTO->level = $request->post('level');
        $requestDTO->unit = $request->post('unit');
        $requestDTO->year = $request->post('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $realizationPaperWorkService = new RealizationPaperWorkService($constructRequest);

        $realizationPaperWorkService->update($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Realisasi (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Diubah", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string|int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Lock change.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|int  $id
     * @param  string  $month
     * @return \Illuminate\Http\JsonResponse
     */
    public function lock_change(Request $request, $id, $month)
    {
        $userRepository = new UserRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();
        $realizationRepository = new RealizationRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->realizationRepository = $realizationRepository;

        $realizationPaperWorkValidationService = new RealizationPaperWorkValidationService($constructRequest);

        $validation = $realizationPaperWorkValidationService->lockChangeValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new RealizationPaperWorkChangeLockRequest();

        $requestDTO->indicatorId = $id;
        $requestDTO->month = $month;

        $realizationPaperWorkService = new RealizationPaperWorkService($constructRequest);

        $realizationPaperWorkService->lock_change($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Realisasi (KPI: %s) (Bulan: %s) Berhasil Diubah", $id, $month),
            null,
            null,
        );
    }
}
