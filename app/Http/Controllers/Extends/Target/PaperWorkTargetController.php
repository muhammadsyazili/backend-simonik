<?php

namespace App\Http\Controllers\Extends\Target;

use App\DTO\ConstructRequest;
use App\DTO\TargetPaperWorkEditRequest;
use App\DTO\TargetPaperWorkUpdateRequest;
use App\Http\Controllers\ApiController;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Http\Request;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Services\TargetPaperWorkService;
use App\Services\TargetPaperWorkValidationService;

class PaperWorkTargetController extends ApiController
{
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

        $targetPaperWorkValidationService = new TargetPaperWorkValidationService($constructRequest);

        $validation = $targetPaperWorkValidationService->editValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new TargetPaperWorkEditRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $targetPaperWorkService = new TargetPaperWorkService($constructRequest);

        $response = $targetPaperWorkService->edit($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja Target",
            [
                'indicators' => $response->indicators,
            ],
            null,
        );
    }

    /**
     * Export.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function export(Request $request)
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

        $targetPaperWorkValidationService = new TargetPaperWorkValidationService($constructRequest);

        $validation = $targetPaperWorkValidationService->exportValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new TargetPaperWorkEditRequest();

        $requestDTO->level = $request->query('level');
        $requestDTO->unit = $request->query('unit');
        $requestDTO->year = $request->query('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $targetPaperWorkService = new TargetPaperWorkService($constructRequest);

        $response = $targetPaperWorkService->export($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Kertas Kerja Target - Ekspor",
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
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        //logging
        // $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        // $output->writeln(sprintf('targets: %s', json_encode($request->post('targets'))));

        $userRepository = new UserRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();
        $targetRepository = new TargetRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->targetRepository = $targetRepository;

        $targetPaperWorkValidationService = new TargetPaperWorkValidationService($constructRequest);

        $validation = $targetPaperWorkValidationService->updateValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new TargetPaperWorkUpdateRequest();

        $requestDTO->indicators = array_keys($request->post('targets'));
        $requestDTO->targets = $request->post('targets');
        $requestDTO->level = $request->post('level');
        $requestDTO->unit = $request->post('unit');
        $requestDTO->year = $request->post('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $targetPaperWorkService = new TargetPaperWorkService($constructRequest);

        $targetPaperWorkService->update($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Target (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Diubah", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update_import(Request $request)
    {
        //logging
        // $output = new \Symfony\Component\Console\Output\ConsoleOutput();
        // $output->writeln(sprintf('targets: %s', json_encode($request->post('targets'))));

        $userRepository = new UserRepository();
        $levelRepository = new LevelRepository();
        $unitRepository = new UnitRepository();
        $indicatorRepository = new IndicatorRepository();
        $targetRepository = new TargetRepository();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $userRepository;
        $constructRequest->levelRepository = $levelRepository;
        $constructRequest->unitRepository = $unitRepository;
        $constructRequest->indicatorRepository = $indicatorRepository;
        $constructRequest->targetRepository = $targetRepository;

        $targetPaperWorkValidationService = new TargetPaperWorkValidationService($constructRequest);

        $validation = $targetPaperWorkValidationService->updateImportValidation($request);

        if ($validation->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validation->errors(),
            );
        }

        $requestDTO = new TargetPaperWorkUpdateRequest();

        $requestDTO->indicators = array_keys($request->post('targets'));
        $requestDTO->targets = $request->post('targets');
        $requestDTO->level = $request->post('level');
        $requestDTO->unit = $request->post('unit');
        $requestDTO->year = $request->post('tahun');
        $requestDTO->userId = $request->header('X-User-Id');

        $targetPaperWorkService = new TargetPaperWorkService($constructRequest);

        $targetPaperWorkService->update_import($requestDTO);

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            sprintf("Kertas Kerja Target (Level: %s) (Unit: %s) (Tahun: %s) Berhasil Diubah", strtoupper($requestDTO->level), strtoupper($requestDTO->unit), strtoupper($requestDTO->year)),
            null,
            null,
        );
    }
}
