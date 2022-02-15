<?php

namespace App\Services;

use App\Domains\Unit;
use App\DTO\ConstructRequest;
use App\DTO\UnitCreateOrEditResponse;
use App\DTO\UnitInsertOrUpdateRequest;
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
    public function index()
    {
        return $this->unitRepository->find__all__with__level_parent();
    }

    //use repo LevelRepository, UnitRepository, UserRepository
    public function create(string|int $userId): UnitCreateOrEditResponse
    {
        $response = new UnitCreateOrEditResponse();

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, false);
        $response->units = $this->unitRepository->find__all();

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository, RealizationRepository
    public function store(UnitInsertOrUpdateRequest $unitRequest): void
    {
        DB::transaction(function () use ($unitRequest) {
            $unitDomain = new Unit();

            $parent_level__uppercase = strtoupper($unitRequest->parent_level);
            $parent_level__lowercase = strtolower($unitRequest->parent_level);

            $name__uppercase = strtoupper($unitRequest->name);
            $name__lowercase = strtolower($unitRequest->name);

            $unitDomain->id = (string) Str::orderedUuid();
            $unitDomain->name = "$parent_level__uppercase - $name__uppercase";
            $unitDomain->slug = Str::slug("$parent_level__lowercase-$name__lowercase");
            $unitDomain->level_id = $this->levelRepository->find__id__by__slug($unitRequest->parent_level);
            $unitDomain->parent_id = $this->unitRepository->find__id__by__slug($unitRequest->parent_unit);

            $this->unitRepository->save($unitDomain);

            //buat kertas kerja KPI tahun saat ini, jika sudah tersedia
            // $constructRequest = new ConstructRequest();

            // $constructRequest->indicatorRepository = $this->indicatorRepository;
            // $constructRequest->targetRepository = $this->targetRepository;
            // $constructRequest->realizationRepository = $this->realizationRepository;

            // $IndicatorPaperWorkService = new IndicatorPaperWorkService($constructRequest);

            // $IndicatorPaperWorkService->storeFromMaster($unitDomain->level_id, $unitDomain->id, (string) now()->year, $unitRequest->userId);
        });
    }

    //use repo LevelRepository, UnitRepository
    public function unitsOfLevel(string $slug)
    {
        return $this->unitRepository->find__allSlug_allName__by__levelId($this->levelRepository->find__id__by__slug($slug));
    }
}
