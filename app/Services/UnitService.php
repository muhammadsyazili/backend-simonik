<?php

namespace App\Services;

use App\Domains\Unit;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorPaperWorkStoreFromMasterRequest;
use App\DTO\UnitCreateResponse;
use App\DTO\UnitCreateRequest;
use App\DTO\UnitDestroyRequest;
use App\DTO\UnitEditRequest;
use App\DTO\UnitEditResponse;
use App\DTO\UnitIndexResponse;
use App\DTO\UnitStoreRequest;
use App\DTO\UnitUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UnitService
{
    private ?UserRepository $userRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?TargetRepository $targetRepository;
    private ?RealizationRepository $realizationRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->targetRepository = $constructRequest->targetRepository;
        $this->realizationRepository = $constructRequest->realizationRepository;
    }

    //use repo UnitRepository
    public function index(): UnitIndexResponse
    {
        $response = new UnitIndexResponse();

        $units = $this->unitRepository->find__all__with__level_parent();

        $newUnits = $units->map(function ($item) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'slug' => $item->slug,
                'parent_name' => is_null($item->parent) ? '-' : $item->parent->name,
                'level_name' => $item->level->name,
            ];
        });

        $response->units = $newUnits;

        return $response;
    }

    //use repo LevelRepository, UnitRepository, UserRepository
    public function create(UnitCreateRequest $unitRequest): UnitCreateResponse
    {
        $response = new UnitCreateResponse();

        $userId = $unitRequest->userId;

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levels_of_user($userId, false);

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository, RealizationRepository
    public function store(UnitStoreRequest $unitRequest): void
    {
        DB::transaction(function () use ($unitRequest) {
            $unitDomain = new Unit();

            $level__uppercase = strtoupper($unitRequest->level);
            $level__lowercase = strtolower($unitRequest->level);

            $name__uppercase = strtoupper($unitRequest->name);
            $name__lowercase = strtolower($unitRequest->name);

            $unitDomain->id = (string) Str::orderedUuid();
            $unitDomain->name = "$level__uppercase - $name__uppercase";
            $unitDomain->slug = Str::slug("$level__lowercase-$name__lowercase");
            $unitDomain->level_id = $this->levelRepository->find__id__by__slug($unitRequest->level);
            $unitDomain->parent_id = is_null($unitRequest->parentUnit) ? null : $this->unitRepository->find__id__by__slug($unitRequest->parentUnit);

            $this->unitRepository->save($unitDomain);

            //buat kertas kerja indikator tahun saat ini, jika sudah tersedia
            $constructRequest = new ConstructRequest();

            $constructRequest->indicatorRepository = $this->indicatorRepository;
            $constructRequest->targetRepository = $this->targetRepository;
            $constructRequest->realizationRepository = $this->realizationRepository;

            $requestDTO = new IndicatorPaperWorkStoreFromMasterRequest();

            $requestDTO->levelId = $unitDomain->level_id;
            $requestDTO->unitId = $this->unitRepository->find__id__by__slug($unitDomain->slug);
            $requestDTO->year = (string) now()->year;
            $requestDTO->userId = $unitRequest->userId;

            $IndicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

            $IndicatorPaperWorkService->storeFromMaster($requestDTO);
        });
    }

    //use repo LevelRepository, UnitRepository, UserRepository
    public function edit(UnitEditRequest $unitRequest): UnitEditResponse
    {
        $response = new UnitEditResponse();

        $unit = $this->unitRepository->find__by__id($unitRequest->id);

        $unit = [
            'id' => $unit->id,
            'name' => $unit->name,
            'parent_id' => $unit->parent_id,
            'level_id' => $unit->level_id,
        ];

        $response->unit = $unit;

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levels_of_user($unitRequest->userId, false);

        return $response;
    }

    //use repo LevelRepository, UnitRepository
    public function update(UnitUpdateRequest $unitRequest): void
    {
        DB::transaction(function () use ($unitRequest) {
            $unitDomain = new Unit();

            $unit = $this->unitRepository->find__with__level__by__id($unitRequest->id);

            $unitRequest->name = str_replace($unit->level->name . ' - ', '', $unitRequest->name); //menghapus prefix

            $level__uppercase = strtoupper($unitRequest->level);
            $level__lowercase = strtolower($unitRequest->level);

            $name__uppercase = strtoupper($unitRequest->name);
            $name__lowercase = strtolower($unitRequest->name);

            $unitDomain->id = $unitRequest->id;
            $unitDomain->name = "$level__uppercase - $name__uppercase";
            $unitDomain->slug = Str::slug("$level__lowercase-$name__lowercase");
            $unitDomain->level_id = $this->levelRepository->find__id__by__slug($unitRequest->level);
            $unitDomain->parent_id = is_null($unitRequest->parentUnit) ? null : $this->unitRepository->find__id__by__slug($unitRequest->parentUnit);

            $this->unitRepository->update__by__id($unitDomain);
        });
    }

    //use repo UnitRepository
    public function destroy(UnitDestroyRequest $levelRequest): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($levelRequest) {
            $this->unitRepository->delete__by__id($levelRequest->id);
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    //use repo LevelRepository, UnitRepository
    public function units_of_level(string $slug)
    {
        return $this->unitRepository->find__allSlug_allName__by__levelId($this->levelRepository->find__id__by__slug($slug));
    }
}
