<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorPaperWorkEditResponse;
use App\DTO\IndicatorPaperWorkCreateRequest;
use App\DTO\IndicatorPaperWorkCreateResponse;
use App\DTO\IndicatorPaperWorkDestroyRequest;
use App\DTO\IndicatorPaperWorkEditRequest;
use App\DTO\IndicatorPaperWorkIndexRequest;
use App\DTO\IndicatorPaperWorkIndexResponse;
use App\DTO\IndicatorPaperWorkReorderRequest;
use App\DTO\IndicatorPaperWorkStoreRequest;
use App\DTO\IndicatorPaperWorkUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicatorPaperWorkService
{
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;
    private ?UserRepository $userRepository;
    private ?TargetRepository $targetRepository;
    private ?RealizationRepository $realizationRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->unitRepository = $constructRequest->unitRepository;
        $this->userRepository = $constructRequest->userRepository;
        $this->targetRepository = $constructRequest->targetRepository;
        $this->realizationRepository = $constructRequest->realizationRepository;
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository, UserRepository
    public function index(IndicatorPaperWorkIndexRequest $indicatorPaperWorkRequest): IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $level = $indicatorPaperWorkRequest->level;
        $unit = $indicatorPaperWorkRequest->unit;
        $year = $indicatorPaperWorkRequest->year;
        $userId = $indicatorPaperWorkRequest->userId;

        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

        $isSuperMaster = $level === 'super-master' ? true : false;
        $isSuperAdmin = $user->role->name === 'super-admin';
        $isSuperAdminOrAdmin = $isSuperAdmin || $user->role->name === 'admin';

        $currentLevelNotSameWithUserLevel = true;
        if ($user->role->name === 'super-admin') {
            $currentLevelNotSameWithUserLevel = true;
        } else {
            $currentLevelNotSameWithUserLevel = $level === $user->unit->level->slug ? false : true;
        }

        // 'permissions paper work indicator (create, edit, delete)' handler
        $numberOfChildLevel = $isSuperAdmin ? count($this->levelRepository->find__allSlug__with__childs__by__root()) : count($this->levelRepository->find__allFlattenSlug__with__this_childs__by__id($user->unit->level->id));

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, true);

        $response->indicators = $level === 'super-master' ? $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null) : $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year($unit === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($level), $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit), $year);

        $response->permissions = [
            'indicator' => [
                'create' => $isSuperAdmin ? true : false,
                'edit' => $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
                'delete' => $isSuperAdmin && $isSuperMaster ? true : false,
                'changes_order' => $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false
            ],
            'reference' => [
                'create' => $isSuperAdmin ? true : false,
                'edit' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
            ],
            'paper_work' => ['indicator' => [
                'create' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin ? true : false,
                'edit' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
                'delete' => ($numberOfChildLevel > 1) && $isSuperAdminOrAdmin && $currentLevelNotSameWithUserLevel ? true : false,
            ]],
        ];

        return $response;
    }

    //use repo IndicatorRepository, LevelRepository, UserRepository
    public function create(IndicatorPaperWorkCreateRequest $indicatorPaperWorkRequest): IndicatorPaperWorkCreateResponse
    {
        $response = new IndicatorPaperWorkCreateResponse();

        $userId = $indicatorPaperWorkRequest->userId;

        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

        $parentId = $user->role->name === 'super-admin' ? $this->levelRepository->find__allId__by__root() : $this->levelRepository->find__allId__by__id($user->unit->level->id);

        $response->levels = $this->levelRepository->find__all__with__childs__by__parentIdList(Arr::flatten($parentId));
        $response->indicators = $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null);

        return $response;
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository, TargetRepository, RealizationRepository
    public function store(IndicatorPaperWorkStoreRequest $indicatorPaperWorkRequest): void
    {
        $indicators = $indicatorPaperWorkRequest->indicators;
        $level = $indicatorPaperWorkRequest->level;
        $year = $indicatorPaperWorkRequest->year;
        $userId = $indicatorPaperWorkRequest->userId;

        DB::transaction(function () use ($indicators, $level, $year, $userId) {
            $indicatorDomain = new Indicator();
            $targetDomain = new Target();
            $realizationDomain = new Realization();

            $levelId = $this->levelRepository->find__id__by__slug($level);

            //membuat nasab KPI
            $pathsOfSelectedIndicator = [];
            foreach ($indicators as $value) {
                $pathsOfSelectedIndicator = array_merge($pathsOfSelectedIndicator, $this->indicatorRepository->find__all__with__parents__by__id($value));
            }

            //nasab KPI
            $superMasterIndicators = $this->indicatorRepository->find__all__by__idList(array_unique($pathsOfSelectedIndicator));

            //section: paper work 'MASTER' creating ----------------------------------------------------------------------

            //build ID
            $idListMaster = [];
            foreach ($superMasterIndicators as $superMasterIndicator) {
                $idListMaster[$superMasterIndicator->id] = (string) Str::orderedUuid();
            }

            $i = 0;
            foreach ($superMasterIndicators as $superMasterIndicator) {
                $indicatorDomain->id = $idListMaster[$superMasterIndicator->id];
                $indicatorDomain->indicator = $superMasterIndicator->indicator;
                $indicatorDomain->formula = $superMasterIndicator->formula;
                $indicatorDomain->measure = $superMasterIndicator->measure;
                $indicatorDomain->weight = $superMasterIndicator->getRawOriginal('weight');
                $indicatorDomain->polarity = $superMasterIndicator->getRawOriginal('polarity');
                $indicatorDomain->year = $year;
                $indicatorDomain->reducing_factor = $superMasterIndicator->reducing_factor;
                $indicatorDomain->validity = $superMasterIndicator->getRawOriginal('validity');
                $indicatorDomain->reviewed = $superMasterIndicator->reviewed;
                $indicatorDomain->referenced = $superMasterIndicator->referenced;
                $indicatorDomain->dummy = $superMasterIndicator->dummy;
                $indicatorDomain->label = 'master';
                $indicatorDomain->unit_id = null;
                $indicatorDomain->level_id = $levelId;
                $indicatorDomain->order = $i + 1;
                $indicatorDomain->code = $superMasterIndicator->code;
                $indicatorDomain->parent_vertical_id = $superMasterIndicator->id;
                $indicatorDomain->parent_horizontal_id = is_null($superMasterIndicator->parent_horizontal_id) ? null : $idListMaster[$superMasterIndicator->parent_horizontal_id];
                $indicatorDomain->created_by = $userId;

                $this->indicatorRepository->save($indicatorDomain);

                //target 'MASTER' creating
                if (!is_null($superMasterIndicator->validity)) {
                    foreach ($superMasterIndicator->validity as $validityKey => $validityValue) {
                        $targetDomain->id = (string) Str::orderedUuid();
                        $targetDomain->indicator_id = $idListMaster[$superMasterIndicator->id];
                        $targetDomain->month = $validityKey;
                        $targetDomain->value = 0;
                        $targetDomain->locked = false;
                        $targetDomain->default = true;

                        $this->targetRepository->save($targetDomain);
                    }
                }
                $i++;
            }
            //end section: paper work 'MASTER' creating ----------------------------------------------------------------------

            //section: paper work 'CHILD' creating ----------------------------------------------------------------------
            $units = $this->unitRepository->find__all__by__levelId($levelId);
            $masterIndicators = $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, null, $year);

            foreach ($units as $unit) {
                //build ID
                $idListChild = [];
                foreach ($masterIndicators as $masterIndicator) {
                    $idListChild[$masterIndicator->id] = (string) Str::orderedUuid();
                }

                $i = 0;
                foreach ($masterIndicators as $masterIndicator) {
                    $indicatorDomain->id = $idListChild[$masterIndicator->id];
                    $indicatorDomain->indicator = $masterIndicator->indicator;
                    $indicatorDomain->formula = $masterIndicator->formula;
                    $indicatorDomain->measure = $masterIndicator->measure;
                    $indicatorDomain->weight = $masterIndicator->getRawOriginal('weight');
                    $indicatorDomain->polarity = $masterIndicator->getRawOriginal('polarity');
                    $indicatorDomain->year = $year;
                    $indicatorDomain->reducing_factor = $masterIndicator->reducing_factor;
                    $indicatorDomain->validity = $masterIndicator->getRawOriginal('validity');
                    $indicatorDomain->reviewed = $masterIndicator->reviewed;
                    $indicatorDomain->referenced = $masterIndicator->referenced;
                    $indicatorDomain->dummy = $masterIndicator->dummy;
                    $indicatorDomain->label = 'child';
                    $indicatorDomain->unit_id = $unit->id;
                    $indicatorDomain->level_id = $levelId;
                    $indicatorDomain->order = $i + 1;
                    $indicatorDomain->code = $masterIndicator->code;
                    $indicatorDomain->parent_vertical_id = $masterIndicator->id;
                    $indicatorDomain->parent_horizontal_id = is_null($masterIndicator->parent_horizontal_id) ? null : $idListChild[$masterIndicator->parent_horizontal_id];
                    $indicatorDomain->created_by = $userId;

                    $this->indicatorRepository->save($indicatorDomain);

                    //target & realisasi 'CHILD' creating
                    if (!is_null($masterIndicator->validity)) {
                        foreach ($masterIndicator->validity as $validityKey => $validityValue) {
                            $targetDomain->id = (string) Str::orderedUuid();
                            $targetDomain->indicator_id = $idListChild[$masterIndicator->id];
                            $targetDomain->month = $validityKey;
                            $targetDomain->value = 0;
                            $targetDomain->locked = false;
                            $targetDomain->default = true;

                            $this->targetRepository->save($targetDomain);

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $idListChild[$masterIndicator->id];
                            $realizationDomain->month = $validityKey;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain);
                        }
                    }
                    $i++;
                }
            }
            //end section: paper work 'CHILD' creating ----------------------------------------------------------------------
        });
    }

    //use repo IndicatorRepository, TargetRepository, RealizationRepository
    public function storeFromMaster(string|int $levelId, string|int $unitId, string $year, string|int $userId): void
    {
        DB::transaction(function () use ($levelId, $unitId, $year, $userId) {
            $indicatorDomain = new Indicator();
            $targetDomain = new Target();
            $realizationDomain = new Realization();

            $masterIndicators = $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, null, $year);

            if (count($masterIndicators) !== 0) { //kertas kerja KPI sudah tersedia
                # code...
                //build ID
                $idListChild = [];
                foreach ($masterIndicators as $masterIndicator) {
                    $idListChild[$masterIndicator->id] = (string) Str::orderedUuid();
                }

                $i = 0;
                foreach ($masterIndicators as $masterIndicator) {
                    $indicatorDomain->id = $idListChild[$masterIndicator->id];
                    $indicatorDomain->indicator = $masterIndicator->indicator;
                    $indicatorDomain->formula = $masterIndicator->formula;
                    $indicatorDomain->measure = $masterIndicator->measure;
                    $indicatorDomain->weight = $masterIndicator->getRawOriginal('weight');
                    $indicatorDomain->polarity = $masterIndicator->getRawOriginal('polarity');
                    $indicatorDomain->year = $year;
                    $indicatorDomain->reducing_factor = $masterIndicator->reducing_factor;
                    $indicatorDomain->validity = $masterIndicator->getRawOriginal('validity');
                    $indicatorDomain->reviewed = $masterIndicator->reviewed;
                    $indicatorDomain->referenced = $masterIndicator->referenced;
                    $indicatorDomain->dummy = $masterIndicator->dummy;
                    $indicatorDomain->label = 'child';
                    $indicatorDomain->unit_id = $unitId;
                    $indicatorDomain->level_id = $levelId;
                    $indicatorDomain->order = $i + 1;
                    $indicatorDomain->code = $masterIndicator->code;
                    $indicatorDomain->parent_vertical_id = $masterIndicator->id;
                    $indicatorDomain->parent_horizontal_id = is_null($masterIndicator->parent_horizontal_id) ? null : $idListChild[$masterIndicator->parent_horizontal_id];
                    $indicatorDomain->created_by = $userId;

                    $this->indicatorRepository->save($indicatorDomain);

                    //target & realisasi 'CHILD' creating
                    if (!is_null($masterIndicator->validity)) {
                        foreach ($masterIndicator->validity as $validityKey => $validityValue) {
                            $targetDomain->id = (string) Str::orderedUuid();
                            $targetDomain->indicator_id = $idListChild[$masterIndicator->id];
                            $targetDomain->month = $validityKey;
                            $targetDomain->value = 0;
                            $targetDomain->locked = false;
                            $targetDomain->default = true;

                            $this->targetRepository->save($targetDomain);

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $idListChild[$masterIndicator->id];
                            $realizationDomain->month = $validityKey;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain);
                        }
                    }
                    $i++;
                }
            }
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function edit(IndicatorPaperWorkEditRequest $indicatorPaperWorkRequest): IndicatorPaperWorkEditResponse
    {
        $response = new IndicatorPaperWorkEditResponse;

        $level = $indicatorPaperWorkRequest->level;
        $unit = $indicatorPaperWorkRequest->unit;
        $year = $indicatorPaperWorkRequest->year;

        $response->super_master_indicators = $this->indicatorRepository->find__all__with__childs_referenced__by__superMasterLabel();

        $levelId = $this->levelRepository->find__id__by__slug($level);

        $response->indicators = $unit === 'master' ? $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, null, $year) : $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository, RealizationRepository, UserRepository
    public function update(IndicatorPaperWorkUpdateRequest $indicatorPaperWorkRequest): void
    {
        $indicatorsFromInput = $indicatorPaperWorkRequest->indicators;
        $level = $indicatorPaperWorkRequest->level;
        $unit = $indicatorPaperWorkRequest->unit;
        $year = $indicatorPaperWorkRequest->year;
        $userId = $indicatorPaperWorkRequest->userId;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($indicatorsFromInput, $level, $unit, $year, $userId) {
            $indicatorDomain = new Indicator();
            $targetDomain = new Target();
            $realizationDomain = new Realization();

            $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

            $levelId = $this->levelRepository->find__id__by__slug($level);
            $unitId = $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit);

            if ($unit === 'master') {
                //section: 'MASTER' updating ----------------------------------------------------------------------

                //daftar 'id' dari KPI-KPI 'master'
                $oldIndicatorsMasterOnlyId = $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $year);

                //daftar 'code' dari KPI-KPI 'master'
                $oldIndicatorsMasterOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year);

                $newIndicatorsMaster = [];
                $i = 0;
                foreach ($indicatorsFromInput as $indicatorFromInput) {
                    if (!in_array($indicatorFromInput, $oldIndicatorsMasterOnlyId)) {
                        $newIndicatorsMaster[$i] = $indicatorFromInput;
                        $i++;
                    }
                }

                $newIndicatorsChild = $newIndicatorsMaster;

                $oldIndicatorsMaster = [];
                $i = 0;
                foreach ($oldIndicatorsMasterOnlyId as $oldIndicatorMasterOnlyId) {
                    if (!in_array($oldIndicatorMasterOnlyId, $indicatorsFromInput)) {
                        $oldIndicatorsMaster[$i] = $oldIndicatorMasterOnlyId;
                        $i++;
                    }
                }

                $newIndicatorsNotExisInMaster = []; //daftar KPI baru yang belum terdaftar di 'master'
                $i = 0;
                foreach ($newIndicatorsMaster as $newIndicatorMaster) {
                    if (!in_array($newIndicatorMaster, $oldIndicatorsMasterOnlyCode)) {
                        $newIndicatorsNotExisInMaster[$i] = $newIndicatorMaster;
                        $i++;
                    }
                }

                if (count($newIndicatorsNotExisInMaster) > 0) {

                    //nasab KPI baru yang belum terdaftar di 'master'.
                    $pathsNewIndicators = [];
                    foreach ($newIndicatorsNotExisInMaster as $newIndicatorNotExisInMaster) {
                        $pathsNewIndicators = array_merge($pathsNewIndicators, $this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInMaster));
                    }

                    //gabungan daftar KPI master & KPI baru yang belum terdaftar di 'master'.
                    $mergePathsNewAndOldIndicator = array_unique(array_merge($pathsNewIndicators, $oldIndicatorsMasterOnlyCode));

                    //menghapus item yang 'null'
                    $temp = [];
                    $i = 0;
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!is_null($mergePathNewAndOldIndicator)) {
                            $temp[$i] = $mergePathNewAndOldIndicator;
                            $i++;
                        }
                    }

                    $mergePathsNewAndOldIndicator = $temp;

                    //build ID
                    $idListMaster = [];
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!is_null($mergePathNewAndOldIndicator)) {
                            $idListMaster[$mergePathNewAndOldIndicator] = (string) Str::orderedUuid();
                        }
                    }

                    $indicatorsIdSuspended = [];
                    $i = 0;
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!in_array($mergePathNewAndOldIndicator, $oldIndicatorsMasterOnlyCode)) { //KPI belum terdaftar di 'master'

                            $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator);

                            $havePathsIndicatorNotRegistedInMaster = [];
                            $j = 0;
                            foreach ($pathsNewIndicator as $pathNewIndicator) {
                                if (($pathNewIndicator !== $mergePathNewAndOldIndicator) && !in_array($pathNewIndicator, $oldIndicatorsMasterOnlyCode) && !is_null($pathNewIndicator)) {
                                    $havePathsIndicatorNotRegistedInMaster[$j] = $pathNewIndicator;
                                    $j++;
                                }
                            }

                            if (count($havePathsIndicatorNotRegistedInMaster) === 0 && in_array($mergePathNewAndOldIndicator, $oldIndicatorsMasterOnlyCode)) {

                                $indicatorSuperMaster = $this->indicatorRepository->find__by__id($mergePathNewAndOldIndicator); //get KPI 'super-master' by id

                                $indicatorDomain->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomain->year = $year;
                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomain->label = 'master';
                                $indicatorDomain->unit_id = null;
                                $indicatorDomain->level_id = $levelId;
                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                $indicatorDomain->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomain->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                $indicatorDomain->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomain);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityvalue) {
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomain->month = $validityKey;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = false;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain);
                                    }
                                }
                            } else {
                                $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                $i++;
                            }
                        }
                    }

                    while (count($indicatorsIdSuspended) > 0) {
                        for ($i = 0; $i < count($indicatorsIdSuspended); $i++) {

                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                            if (is_null($indicatorSuperMaster->parent_horizontal_id)) {
                                $indicatorDomain->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomain->year = $year;
                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomain->label = 'master';
                                $indicatorDomain->unit_id = null;
                                $indicatorDomain->level_id = $levelId;
                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                $indicatorDomain->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomain->parent_horizontal_id = null;
                                $indicatorDomain->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomain);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomain->month = $validityKey;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = false;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain);
                                    }
                                }

                                //remove & replace KPI suspended
                                $temp = [];
                                $j = 0;
                                foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                    if ($indicatorSuspended !== $indicatorsIdSuspended[$i]) {
                                        $temp[$j] = $indicatorSuspended;
                                        $j++;
                                    }
                                }
                                $indicatorsIdSuspended = $temp;
                            } else {
                                $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]);

                                foreach ($pathsNewIndicator as $pathNewIndicator) {
                                    if (!is_null($pathNewIndicator)) {

                                        $oldIndicatorsMasterOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year);

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                        if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                            //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'master'
                                            $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);

                                            if (($sum > 0) && !in_array($indicatorSuperMaster->id, $oldIndicatorsMasterOnlyCode)) { //parent_horizontal_id KPI baru sudah terdaftar di master, tapi KPI baru bukan anggota KPI lama
                                                $indicatorDomain->id = $idListMaster[$indicatorSuperMaster->id];
                                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                $indicatorDomain->year = $year;
                                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                                $indicatorDomain->label = 'master';
                                                $indicatorDomain->unit_id = null;
                                                $indicatorDomain->level_id = $levelId;
                                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                                $indicatorDomain->parent_vertical_id = $indicatorSuperMaster->id;
                                                $indicatorDomain->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                                $indicatorDomain->created_by = $userId;

                                                $this->indicatorRepository->save($indicatorDomain);

                                                //target 'MASTER' creating
                                                if (!is_null($indicatorSuperMaster->validity)) {
                                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                        $targetDomain->id = (string) Str::orderedUuid();
                                                        $targetDomain->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                                        $targetDomain->month = $validityKey;
                                                        $targetDomain->value = 0;
                                                        $targetDomain->locked = false;
                                                        $targetDomain->default = true;

                                                        $this->targetRepository->save($targetDomain);
                                                    }
                                                }

                                                //remove & replace KPI suspended
                                                $temp = [];
                                                $j = 0;
                                                foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                                    if ($indicatorSuspended !== $pathNewIndicator) {
                                                        $temp[$j] = $indicatorSuspended;
                                                        $j++;
                                                    }
                                                }
                                                $indicatorsIdSuspended = $temp;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                //section: 'CHILD' updating ----------------------------------------------------------------------

                $units = $this->unitRepository->find__all__by__levelId($levelId);

                //daftar 'code' dari KPI-KPI 'child'
                $oldIndicatorsMasterOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year);

                //daftar 'id' dari KPI-KPI 'master' yang di un-checked
                $oldIndicatorsMasterUnchecked = $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($oldIndicatorsMaster, $levelId, null, $year);

                //jika role user adalah 'super-admin' maka implementasi diterapkan pada semua unit, selain itu hanya unit turunannya
                if ($user->role->name === 'super-admin') {
                    foreach ($units as $unit) {
                        //daftar 'id' dari KPI-KPI 'child'
                        $oldIndicatorsChildOnlyId = $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $unit->id, $year);

                        //daftar 'code' dari KPI-KPI 'child'
                        $oldIndicatorsChildOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year);

                        $newIndicatorsNotExisInChild = []; //daftar KPI baru yang belum terdaftar di 'child'
                        $i = 0;
                        foreach ($newIndicatorsChild as $newIndicatorChild) {
                            if (!in_array($newIndicatorChild, $oldIndicatorsChildOnlyCode)) {
                                $newIndicatorsNotExisInChild[$i] = $newIndicatorChild;
                                $i++;
                            }
                        }

                        $indicatorsExisInMasterButNotExisInChild = []; //daftar KPI sudah terdaftar di 'master', tapi belum terdaftar di 'child'
                        $i = 0;
                        foreach ($oldIndicatorsMasterOnlyCode as $oldIndicatorMasterOnlyCode) {
                            if (!in_array($oldIndicatorMasterOnlyCode, $oldIndicatorsChildOnlyCode)) {
                                $indicatorsExisInMasterButNotExisInChild[$i] = $oldIndicatorMasterOnlyCode;
                                $i++;
                            }
                        }

                        $newIndicatorsNotExisInChild = array_unique(array_merge($newIndicatorsNotExisInChild, $indicatorsExisInMasterButNotExisInChild));

                        //menghapus item yang 'null'
                        $temp = [];
                        $i = 0;
                        foreach ($newIndicatorsNotExisInChild as $newIndicatorNotExisInChild) {
                            if (!is_null($newIndicatorNotExisInChild)) {
                                $temp[$i] = $newIndicatorNotExisInChild;
                                $i++;
                            }
                        }

                        $newIndicatorsNotExisInChild = $temp;

                        if (count($newIndicatorsNotExisInChild) > 0) {

                            //nasab KPI baru yang belum terdaftar di 'child'.
                            $pathsNewIndicators = [];
                            foreach ($newIndicatorsNotExisInChild as $newIndicatorNotExisInChild) {
                                $pathsNewIndicators = array_merge($pathsNewIndicators, $this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInChild));
                            }

                            //gabungan daftar KPI child & KPI baru yang belum terdaftar di 'child'.
                            $mergePathsNewAndOldIndicator = array_unique(array_merge($pathsNewIndicators, $oldIndicatorsChildOnlyCode));

                            //menghapus item yang 'null'
                            $temp = [];
                            $i = 0;
                            foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                                if (!is_null($mergePathNewAndOldIndicator)) {
                                    $temp[$i] = $mergePathNewAndOldIndicator;
                                    $i++;
                                }
                            }

                            $mergePathsNewAndOldIndicator = $temp;

                            //build ID
                            $idListChild = [];
                            foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                                if (!is_null($mergePathNewAndOldIndicator)) {
                                    $idListChild[$mergePathNewAndOldIndicator] = (string) Str::orderedUuid();
                                }
                            }

                            $indicatorsIdSuspended = [];
                            $i = 0;
                            foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                                if (!in_array($mergePathNewAndOldIndicator, $oldIndicatorsChildOnlyCode)) { //KPI belum terdaftar di 'child'

                                    $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator);

                                    $havePathsIndicatorNotRegistedInChild = [];
                                    $j = 0;
                                    foreach ($pathsNewIndicator as $pathNewIndicator) {
                                        if (!is_null($pathNewIndicator) && ($pathNewIndicator !== $mergePathNewAndOldIndicator) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) {
                                            $havePathsIndicatorNotRegistedInChild[$j] = $pathNewIndicator;
                                            $j++;
                                        }
                                    }

                                    if (count($havePathsIndicatorNotRegistedInChild) === 0 && in_array($mergePathNewAndOldIndicator, $oldIndicatorsChildOnlyCode)) {

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($mergePathNewAndOldIndicator); //get KPI 'super-master' by id

                                        $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                        $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                        $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                        $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                        $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                        $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                        $indicatorDomain->year = $year;
                                        $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                        $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                        $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                        $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                        $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                        $indicatorDomain->label = 'child';
                                        $indicatorDomain->unit_id = $unit->id;
                                        $indicatorDomain->level_id = $levelId;
                                        $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                        $indicatorDomain->code = $indicatorSuperMaster->code;
                                        $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                        $indicatorDomain->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                        $indicatorDomain->created_by = $userId;

                                        $this->indicatorRepository->save($indicatorDomain);

                                        //target & realisasi 'CHILD' creating
                                        if (!is_null($indicatorSuperMaster->validity)) {
                                            foreach ($indicatorSuperMaster->validity as $validitykey => $validityValue) {
                                                $targetDomain->id = (string) Str::orderedUuid();
                                                $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $targetDomain->month = $validitykey;
                                                $targetDomain->value = 0;
                                                $targetDomain->locked = false;
                                                $targetDomain->default = true;

                                                $this->targetRepository->save($targetDomain);

                                                $realizationDomain->id = (string) Str::orderedUuid();
                                                $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $realizationDomain->month = $validitykey;
                                                $realizationDomain->value = 0;
                                                $realizationDomain->locked = true;
                                                $realizationDomain->default = true;

                                                $this->realizationRepository->save($realizationDomain);
                                            }
                                        }
                                    } else {
                                        $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                        $i++;
                                    }
                                }
                            }

                            while (count($indicatorsIdSuspended) > 0) {
                                for ($i = 0; $i < count($indicatorsIdSuspended); $i++) {

                                    $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                                    if ($indicatorSuperMaster->parent_horizontal_id === null) {
                                        $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                        $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                        $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                        $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                        $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                        $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                        $indicatorDomain->year = $year;
                                        $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                        $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                        $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                        $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                        $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                        $indicatorDomain->label = 'child';
                                        $indicatorDomain->unit_id = $unit->id;
                                        $indicatorDomain->level_id = $levelId;
                                        $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                        $indicatorDomain->code = $indicatorSuperMaster->code;
                                        $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                        $indicatorDomain->parent_horizontal_id = null;
                                        $indicatorDomain->created_by = $userId;

                                        $this->indicatorRepository->save($indicatorDomain);

                                        //target & realisasi 'CHILD' creating
                                        if (!is_null($indicatorSuperMaster->validity)) {
                                            foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                $targetDomain->id = (string) Str::orderedUuid();
                                                $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $targetDomain->month = $validityKey;
                                                $targetDomain->value = 0;
                                                $targetDomain->locked = false;
                                                $targetDomain->default = true;

                                                $this->targetRepository->save($targetDomain);

                                                $realizationDomain->id = (string) Str::orderedUuid();
                                                $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $realizationDomain->month = $validityKey;
                                                $realizationDomain->value = 0;
                                                $realizationDomain->locked = true;
                                                $realizationDomain->default = true;

                                                $this->realizationRepository->save($realizationDomain);
                                            }
                                        }

                                        //remove & replace KPI suspended
                                        $temp = [];
                                        $j = 0;
                                        foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                            if ($indicatorSuspended !== $indicatorsIdSuspended[$i]) {
                                                $temp[$j] = $indicatorSuspended;
                                                $j++;
                                            }
                                        }
                                        $indicatorsIdSuspended = $temp;
                                    } else {

                                        $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]);

                                        foreach ($pathsNewIndicator as $pathNewIndicator) {
                                            if (!is_null($pathNewIndicator)) {

                                                $oldIndicatorsChildOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year);

                                                $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                                if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                                    //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child'
                                                    $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);

                                                    if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) { //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child', tapi baru belum terdaftar di 'child'
                                                        $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                                        $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                                        $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                                        $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                                        $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                        $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                        $indicatorDomain->year = $year;
                                                        $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                        $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                        $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                                        $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                                        $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                                        $indicatorDomain->label = 'child';
                                                        $indicatorDomain->unit_id = $unit->id;
                                                        $indicatorDomain->level_id = $levelId;
                                                        $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                                        $indicatorDomain->code = $indicatorSuperMaster->code;
                                                        $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                                        $indicatorDomain->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                                        $indicatorDomain->created_by = $userId;

                                                        $this->indicatorRepository->save($indicatorDomain);

                                                        //target & realisasi 'CHILD' creating
                                                        if (!is_null($indicatorSuperMaster->validity)) {
                                                            foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                                $targetDomain->id = (string) Str::orderedUuid();
                                                                $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                $targetDomain->month = $validityKey;
                                                                $targetDomain->value = 0;
                                                                $targetDomain->locked = false;
                                                                $targetDomain->default = true;

                                                                $this->targetRepository->save($targetDomain);

                                                                $realizationDomain->id = (string) Str::orderedUuid();
                                                                $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                $realizationDomain->month = $validityKey;
                                                                $realizationDomain->value = 0;
                                                                $realizationDomain->locked = true;
                                                                $realizationDomain->default = true;

                                                                $this->realizationRepository->save($realizationDomain);
                                                            }
                                                        }

                                                        //remove & replace KPI suspended
                                                        $temp = [];
                                                        $j = 0;
                                                        foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                                            if ($indicatorSuspended !== $pathNewIndicator) {
                                                                $temp[$j] = $indicatorSuspended;
                                                                $j++;
                                                            }
                                                        }
                                                        $indicatorsIdSuspended = $temp;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }

                        $oldIndicatorsChild = [];
                        $i = 0;
                        foreach ($oldIndicatorsMasterUnchecked as $oldIndicatorMasterUnchecked) {
                            $indicator = $this->indicatorRepository->find__by__code_levelId_unitId_year($oldIndicatorMasterUnchecked->code, $levelId, $unit->id, $year);
                            if (!is_null($indicator)) {
                                $oldIndicatorsChild[$i] = $indicator->id;
                                $i++;
                            }
                        }

                        if (count($oldIndicatorsChild) > 0) { //terdapat 'id' KPI lama yang di un-checked.
                            foreach ($oldIndicatorsChild as $oldIndicatorChild) {
                                $this->targetRepository->delete__by__indicatorId($oldIndicatorChild); //target deleting
                                $this->realizationRepository->delete__by__indicatorId($oldIndicatorChild); //realisasi deleting
                                $this->indicatorRepository->delete__by__id($oldIndicatorChild); //KPI deleting
                            }
                        }
                    }
                } else {
                    $unitsId = $this->unitRepository->find__allFlattenId__with__childs__by__id($user->unit->id); //mengambil daftar 'id' unit-unit turunan berdasarkan user

                    foreach ($units as $unit) {
                        if (in_array($unit->id, $unitsId)) {
                            //daftar 'id' dari KPI-KPI 'child'
                            $oldIndicatorsChildOnlyId = $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $unit->id, $year);

                            //daftar 'code' dari KPI-KPI 'child'
                            $oldIndicatorsChildOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year);

                            $newIndicatorsNotExisInChild = []; //daftar KPI baru yang belum terdaftar di 'child'
                            $i = 0;
                            foreach ($newIndicatorsChild as $newIndicatorChild) {
                                if (!in_array($newIndicatorChild, $oldIndicatorsChildOnlyCode)) {
                                    $newIndicatorsNotExisInChild[$i] = $newIndicatorChild;
                                    $i++;
                                }
                            }

                            $indicatorsExisInMasterButNotExisInChild = []; //daftar KPI sudah terdaftar di 'master', tapi belum terdaftar di 'child'
                            $i = 0;
                            foreach ($oldIndicatorsMasterOnlyCode as $oldIndicatorMasterOnlyCode) {
                                if (!in_array($oldIndicatorMasterOnlyCode, $oldIndicatorsChildOnlyCode)) {
                                    $indicatorsExisInMasterButNotExisInChild[$i] = $oldIndicatorMasterOnlyCode;
                                    $i++;
                                }
                            }

                            $newIndicatorsNotExisInChild = array_unique(array_merge($newIndicatorsNotExisInChild, $indicatorsExisInMasterButNotExisInChild));

                            //menghapus item yang 'null'
                            $temp = [];
                            $i = 0;
                            foreach ($newIndicatorsNotExisInChild as $newIndicatorNotExisInChild) {
                                if (!is_null($newIndicatorNotExisInChild)) {
                                    $temp[$i] = $newIndicatorNotExisInChild;
                                    $i++;
                                }
                            }

                            $newIndicatorsNotExisInChild = $temp;

                            if (count($newIndicatorsNotExisInChild) > 0) {

                                //nasab KPI baru yang belum terdaftar di 'child'.
                                $pathsNewIndicators = [];
                                foreach ($newIndicatorsNotExisInChild as $newIndicatorNotExisInChild) {
                                    $pathsNewIndicators = array_merge($pathsNewIndicators, $this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInChild));
                                }

                                //gabungan daftar KPI child & KPI baru yang belum terdaftar di 'child'.
                                $mergePathsNewAndOldIndicator = array_unique(array_merge($pathsNewIndicators, $oldIndicatorsChildOnlyCode));

                                //menghapus item yang 'null'
                                $temp = [];
                                $i = 0;
                                foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                                    if (!is_null($mergePathNewAndOldIndicator)) {
                                        $temp[$i] = $mergePathNewAndOldIndicator;
                                        $i++;
                                    }
                                }

                                $mergePathsNewAndOldIndicator = $temp;

                                //build ID
                                $idListChild = [];
                                foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                                    if (!is_null($mergePathNewAndOldIndicator)) {
                                        $idListChild[$mergePathNewAndOldIndicator] = (string) Str::orderedUuid();
                                    }
                                }

                                $indicatorsIdSuspended = [];
                                $i = 0;
                                foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                                    if (!in_array($mergePathNewAndOldIndicator, $oldIndicatorsChildOnlyCode)) { //KPI belum terdaftar di 'child'

                                        $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator);

                                        $havePathsIndicatorNotRegistedInChild = [];
                                        $j = 0;
                                        foreach ($pathsNewIndicator as $pathNewIndicator) {
                                            if (!is_null($pathNewIndicator) && ($pathNewIndicator !== $mergePathNewAndOldIndicator) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) {
                                                $havePathsIndicatorNotRegistedInChild[$j] = $pathNewIndicator;
                                                $j++;
                                            }
                                        }

                                        if (count($havePathsIndicatorNotRegistedInChild) === 0 && in_array($mergePathNewAndOldIndicator, $oldIndicatorsChildOnlyCode)) {

                                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($mergePathNewAndOldIndicator); //get KPI 'super-master' by id

                                            $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                            $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                            $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                            $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                            $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                            $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                            $indicatorDomain->year = $year;
                                            $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                            $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                            $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                            $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                            $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                            $indicatorDomain->label = 'child';
                                            $indicatorDomain->unit_id = $unit->id;
                                            $indicatorDomain->level_id = $levelId;
                                            $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                            $indicatorDomain->code = $indicatorSuperMaster->code;
                                            $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                            $indicatorDomain->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                            $indicatorDomain->created_by = $userId;

                                            $this->indicatorRepository->save($indicatorDomain);

                                            //target & realisasi 'CHILD' creating
                                            if (!is_null($indicatorSuperMaster->validity)) {
                                                foreach ($indicatorSuperMaster->validity as $validitykey => $validityValue) {
                                                    $targetDomain->id = (string) Str::orderedUuid();
                                                    $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $targetDomain->month = $validitykey;
                                                    $targetDomain->value = 0;
                                                    $targetDomain->locked = false;
                                                    $targetDomain->default = true;

                                                    $this->targetRepository->save($targetDomain);

                                                    $realizationDomain->id = (string) Str::orderedUuid();
                                                    $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $realizationDomain->month = $validitykey;
                                                    $realizationDomain->value = 0;
                                                    $realizationDomain->locked = true;
                                                    $realizationDomain->default = true;

                                                    $this->realizationRepository->save($realizationDomain);
                                                }
                                            }
                                        } else {
                                            $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                            $i++;
                                        }
                                    }
                                }

                                while (count($indicatorsIdSuspended) > 0) {
                                    for ($i = 0; $i < count($indicatorsIdSuspended); $i++) {

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                                        if ($indicatorSuperMaster->parent_horizontal_id === null) {
                                            $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                            $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                            $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                            $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                            $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                            $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                            $indicatorDomain->year = $year;
                                            $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                            $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                            $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                            $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                            $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                            $indicatorDomain->label = 'child';
                                            $indicatorDomain->unit_id = $unit->id;
                                            $indicatorDomain->level_id = $levelId;
                                            $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                            $indicatorDomain->code = $indicatorSuperMaster->code;
                                            $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                            $indicatorDomain->parent_horizontal_id = null;
                                            $indicatorDomain->created_by = $userId;

                                            $this->indicatorRepository->save($indicatorDomain);

                                            //target & realisasi 'CHILD' creating
                                            if (!is_null($indicatorSuperMaster->validity)) {
                                                foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                    $targetDomain->id = (string) Str::orderedUuid();
                                                    $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $targetDomain->month = $validityKey;
                                                    $targetDomain->value = 0;
                                                    $targetDomain->locked = false;
                                                    $targetDomain->default = true;

                                                    $this->targetRepository->save($targetDomain);

                                                    $realizationDomain->id = (string) Str::orderedUuid();
                                                    $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $realizationDomain->month = $validityKey;
                                                    $realizationDomain->value = 0;
                                                    $realizationDomain->locked = true;
                                                    $realizationDomain->default = true;

                                                    $this->realizationRepository->save($realizationDomain);
                                                }
                                            }

                                            //remove & replace KPI suspended
                                            $temp = [];
                                            $j = 0;
                                            foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                                if ($indicatorSuspended !== $indicatorsIdSuspended[$i]) {
                                                    $temp[$j] = $indicatorSuspended;
                                                    $j++;
                                                }
                                            }
                                            $indicatorsIdSuspended = $temp;
                                        } else {

                                            $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]);

                                            foreach ($pathsNewIndicator as $pathNewIndicator) {
                                                if (!is_null($pathNewIndicator)) {

                                                    $oldIndicatorsChildOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year);

                                                    $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                                    if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                                        //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child'
                                                        $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);

                                                        if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) { //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child', tapi baru belum terdaftar di 'child'
                                                            $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                                            $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                                            $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                                            $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                                            $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                            $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                            $indicatorDomain->year = $year;
                                                            $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                            $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                            $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                                            $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                                            $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                                            $indicatorDomain->label = 'child';
                                                            $indicatorDomain->unit_id = $unit->id;
                                                            $indicatorDomain->level_id = $levelId;
                                                            $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                                            $indicatorDomain->code = $indicatorSuperMaster->code;
                                                            $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                                            $indicatorDomain->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                                            $indicatorDomain->created_by = $userId;

                                                            $this->indicatorRepository->save($indicatorDomain);

                                                            //target & realisasi 'CHILD' creating
                                                            if (!is_null($indicatorSuperMaster->validity)) {
                                                                foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                                    $targetDomain->id = (string) Str::orderedUuid();
                                                                    $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                    $targetDomain->month = $validityKey;
                                                                    $targetDomain->value = 0;
                                                                    $targetDomain->locked = false;
                                                                    $targetDomain->default = true;

                                                                    $this->targetRepository->save($targetDomain);

                                                                    $realizationDomain->id = (string) Str::orderedUuid();
                                                                    $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                    $realizationDomain->month = $validityKey;
                                                                    $realizationDomain->value = 0;
                                                                    $realizationDomain->locked = true;
                                                                    $realizationDomain->default = true;

                                                                    $this->realizationRepository->save($realizationDomain);
                                                                }
                                                            }

                                                            //remove & replace KPI suspended
                                                            $temp = [];
                                                            $j = 0;
                                                            foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                                                if ($indicatorSuspended !== $pathNewIndicator) {
                                                                    $temp[$j] = $indicatorSuspended;
                                                                    $j++;
                                                                }
                                                            }
                                                            $indicatorsIdSuspended = $temp;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }

                            $oldIndicatorsChild = [];
                            $i = 0;
                            foreach ($oldIndicatorsMasterUnchecked as $oldIndicatorMasterUnchecked) {
                                $indicator = $this->indicatorRepository->find__by__code_levelId_unitId_year($oldIndicatorMasterUnchecked->code, $levelId, $unit->id, $year);
                                if (!is_null($indicator)) {
                                    $oldIndicatorsChild[$i] = $indicator->id;
                                    $i++;
                                }
                            }

                            if (count($oldIndicatorsChild) > 0) { //terdapat 'id' KPI lama yang di un-checked.
                                foreach ($oldIndicatorsChild as $oldIndicatorChild) {
                                    $this->targetRepository->delete__by__indicatorId($oldIndicatorChild); //target deleting
                                    $this->realizationRepository->delete__by__indicatorId($oldIndicatorChild); //realisasi deleting
                                    $this->indicatorRepository->delete__by__id($oldIndicatorChild); //KPI deleting
                                }
                            }
                        }
                    }
                }

                //end section: 'CHILD' updating ----------------------------------------------------------------------

                if (count($oldIndicatorsMaster) > 0) { //terdapat 'id' KPI lama yang di un-checked.
                    foreach ($oldIndicatorsMaster as $oldIndicatorMaster) {
                        $this->targetRepository->delete__by__indicatorId($oldIndicatorMaster); //target deleting
                        $this->indicatorRepository->delete__by__id($oldIndicatorMaster); //KPI deleting
                    }
                }
                //end section: 'MASTER' updating ----------------------------------------------------------------------
            } else {
                //daftar 'id' dari KPI-KPI 'child'
                $oldIndicatorsChildOnlyId = $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $unitId, $year);

                $newIndicatorsChild = [];
                $i = 0;
                foreach ($indicatorsFromInput as $indicatorFromInput) {
                    if (!in_array($indicatorFromInput, $oldIndicatorsChildOnlyId)) {
                        $newIndicatorsChild[$i] = $indicatorFromInput;
                        $i++;
                    }
                }

                //section: 'MASTER' updating ----------------------------------------------------------------------

                //daftar 'code' dari KPI-KPI 'master'
                $oldIndicatorsMasterOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year);

                $newIndicatorsNotExisInMaster = []; //daftar KPI baru yang belum terdaftar di 'master'
                $i = 0;
                foreach ($newIndicatorsChild as $newIndicatorChild) {
                    if (!in_array($newIndicatorChild, $oldIndicatorsMasterOnlyCode)) {
                        $newIndicatorsNotExisInMaster[$i] = $newIndicatorChild;
                        $i++;
                    }
                }

                if (count($newIndicatorsNotExisInMaster) > 0) {

                    //nasab KPI baru yang belum terdaftar di 'master'.
                    $pathsNewIndicators = [];
                    foreach ($newIndicatorsNotExisInMaster as $newIndicatorNotExisInMaster) {
                        $pathsNewIndicators = array_merge($pathsNewIndicators, $this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInMaster));
                    }

                    //gabungan daftar KPI master & KPI baru yang belum terdaftar di 'master'.
                    $mergePathsNewAndOldIndicator = array_unique(array_merge($pathsNewIndicators, $oldIndicatorsMasterOnlyCode));

                    //menghapus item yang 'null'
                    $temp = [];
                    $i = 0;
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!is_null($mergePathNewAndOldIndicator)) {
                            $temp[$i] = $mergePathNewAndOldIndicator;
                            $i++;
                        }
                    }

                    $mergePathsNewAndOldIndicator = $temp;

                    //build ID
                    $idListMaster = [];
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!is_null($mergePathNewAndOldIndicator)) {
                            $idListMaster[$mergePathNewAndOldIndicator] = (string) Str::orderedUuid();
                        }
                    }

                    $indicatorsIdSuspended = [];
                    $i = 0;
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!in_array($mergePathNewAndOldIndicator, $oldIndicatorsMasterOnlyCode)) { //KPI belum terdaftar di 'master'

                            $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator);

                            $havePathsIndicatorNotRegistedInMaster = [];
                            $j = 0;
                            foreach ($pathsNewIndicator as $pathNewIndicator) {
                                if (!is_null($pathNewIndicator) && ($pathNewIndicator !== $mergePathNewAndOldIndicator) && !in_array($pathNewIndicator, $oldIndicatorsMasterOnlyCode)) {
                                    $havePathsIndicatorNotRegistedInMaster[$j] = $pathNewIndicator;
                                    $j++;
                                }
                            }

                            if (count($havePathsIndicatorNotRegistedInMaster) === 0 && in_array($mergePathNewAndOldIndicator, $oldIndicatorsMasterOnlyCode)) {

                                $indicatorSuperMaster = $this->indicatorRepository->find__by__id($mergePathNewAndOldIndicator); //get KPI 'super-master' by id

                                $indicatorDomain->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomain->year = $year;
                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomain->label = 'master';
                                $indicatorDomain->unit_id = null;
                                $indicatorDomain->level_id = $levelId;
                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                $indicatorDomain->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomain->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                $indicatorDomain->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomain);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validitykey => $validityvalue) {
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomain->month = $validitykey;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = false;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain);
                                    }
                                }
                            } else {
                                $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                $i++;
                            }
                        }
                    }

                    while (count($indicatorsIdSuspended) > 0) {
                        for ($i = 0; $i < count($indicatorsIdSuspended); $i++) {

                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                            if (is_null($indicatorSuperMaster->parent_horizontal_id)) {
                                $indicatorDomain->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomain->year = $year;
                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomain->label = 'master';
                                $indicatorDomain->unit_id = null;
                                $indicatorDomain->level_id = $levelId;
                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                $indicatorDomain->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomain->parent_horizontal_id = null;
                                $indicatorDomain->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomain);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomain->month = $validityKey;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = false;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain);
                                    }
                                }

                                //remove & replace KPI suspended
                                $temp = [];
                                $j = 0;
                                foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                    if ($indicatorSuspended !== $indicatorsIdSuspended[$i]) {
                                        $temp[$j] = $indicatorSuspended;
                                        $j++;
                                    }
                                }
                                $indicatorsIdSuspended = $temp;
                            } else {
                                $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]);

                                foreach ($pathsNewIndicator as $pathNewIndicator) {
                                    if (!is_null($pathNewIndicator)) {

                                        $oldIndicatorsMasterOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year);

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                        if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                            //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'master'
                                            $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);

                                            if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsMasterOnlyCode)) { //parent_horizontal_id KPI baru sudah terdaftar di master, tapi KPI baru bukan anggota KPI lama
                                                $indicatorDomain->id = $idListMaster[$indicatorSuperMaster->id];
                                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                $indicatorDomain->year = $year;
                                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                                $indicatorDomain->label = 'master';
                                                $indicatorDomain->unit_id = null;
                                                $indicatorDomain->level_id = $levelId;
                                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                                $indicatorDomain->parent_vertical_id = $indicatorSuperMaster->id;
                                                $indicatorDomain->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                                $indicatorDomain->created_by = $userId;

                                                $this->indicatorRepository->save($indicatorDomain);

                                                //target 'MASTER' creating
                                                if (!is_null($indicatorSuperMaster->validity)) {
                                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                        $targetDomain->id = (string) Str::orderedUuid();
                                                        $targetDomain->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                                        $targetDomain->month = $validityKey;
                                                        $targetDomain->value = 0;
                                                        $targetDomain->locked = false;
                                                        $targetDomain->default = true;

                                                        $this->targetRepository->save($targetDomain);
                                                    }
                                                }

                                                //remove & replace KPI suspended
                                                $temp = [];
                                                $j = 0;
                                                foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                                    if ($indicatorSuspended !== $pathNewIndicator) {
                                                        $temp[$j] = $indicatorSuspended;
                                                        $j++;
                                                    }
                                                }
                                                $indicatorsIdSuspended = $temp;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                //end section: 'MASTER' updating ----------------------------------------------------------------------

                //section: 'CHILD' updating ----------------------------------------------------------------------

                //daftar 'code' dari KPI-KPI 'child'
                $oldIndicatorsChildOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unitId, $year);

                $oldIndicatorsChild = [];
                $i = 0;
                foreach ($oldIndicatorsChildOnlyId as $oldIndicatorChildOnlyId) {
                    if (!in_array($oldIndicatorChildOnlyId, $indicatorsFromInput)) {
                        $oldIndicatorsChild[$i] = $oldIndicatorChildOnlyId;
                        $i++;
                    }
                }

                $newIndicatorsNotExisInChild = []; //daftar KPI baru yang belum terdaftar di 'child'
                $i = 0;
                foreach ($newIndicatorsChild as $newIndicatorChild) {
                    if (!in_array($newIndicatorChild, $oldIndicatorsChildOnlyCode)) {
                        $newIndicatorsNotExisInChild[$i] = $newIndicatorChild;
                        $i++;
                    }
                }

                if (count($newIndicatorsNotExisInChild) > 0) {

                    //nasab KPI baru yang belum terdaftar di 'child'.
                    $pathsNewIndicators = [];
                    foreach ($newIndicatorsNotExisInChild as $newIndicatorNotExisInChild) {
                        $pathsNewIndicators = array_merge($pathsNewIndicators, $this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInChild));
                    }

                    //gabungan daftar KPI child & KPI baru yang belum terdaftar di 'child'.
                    $mergePathsNewAndOldIndicator = array_unique(array_merge($pathsNewIndicators, $oldIndicatorsChildOnlyCode));

                    //menghapus item yang 'null'
                    $temp = [];
                    $i = 0;
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!is_null($mergePathNewAndOldIndicator)) {
                            $temp[$i] = $mergePathNewAndOldIndicator;
                            $i++;
                        }
                    }

                    $mergePathsNewAndOldIndicator = $temp;

                    //build ID
                    $idListChild = [];
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!is_null($mergePathNewAndOldIndicator)) {
                            $idListChild[$mergePathNewAndOldIndicator] = (string) Str::orderedUuid();
                        }
                    }

                    $indicatorsIdSuspended = [];
                    $i = 0;
                    foreach ($mergePathsNewAndOldIndicator as $mergePathNewAndOldIndicator) {
                        if (!in_array($mergePathNewAndOldIndicator, $oldIndicatorsChildOnlyCode)) { //KPI belum terdaftar di 'child'

                            $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator);

                            $havePathsIndicatorNotRegistedInChild = [];
                            $j = 0;
                            foreach ($pathsNewIndicator as $pathNewIndicator) {
                                if (!is_null($pathNewIndicator) && ($pathNewIndicator !== $mergePathNewAndOldIndicator) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) {
                                    $havePathsIndicatorNotRegistedInChild[$j] = $pathNewIndicator;
                                    $j++;
                                }
                            }

                            if (count($havePathsIndicatorNotRegistedInChild) === 0 && in_array($mergePathNewAndOldIndicator, $oldIndicatorsChildOnlyCode)) {

                                $indicatorSuperMaster = $this->indicatorRepository->find__by__id($mergePathNewAndOldIndicator); //get KPI 'super-master' by id

                                $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomain->year = $year;
                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomain->label = 'child';
                                $indicatorDomain->unit_id = $unitId;
                                $indicatorDomain->level_id = $levelId;
                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unitId, $year);
                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                $indicatorDomain->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unitId, $year);
                                $indicatorDomain->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomain);

                                //target & realisasi 'CHILD' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validitykey => $validityValue) {
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $targetDomain->month = $validitykey;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = false;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain);

                                        $realizationDomain->id = (string) Str::orderedUuid();
                                        $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $realizationDomain->month = $validitykey;
                                        $realizationDomain->value = 0;
                                        $realizationDomain->locked = true;
                                        $realizationDomain->default = true;

                                        $this->realizationRepository->save($realizationDomain);
                                    }
                                }
                            } else {
                                $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                $i++;
                            }
                        }
                    }

                    while (count($indicatorsIdSuspended) > 0) {
                        for ($i = 0; $i < count($indicatorsIdSuspended); $i++) {

                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                            if ($indicatorSuperMaster->parent_horizontal_id === null) {
                                $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomain->year = $year;
                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomain->label = 'child';
                                $indicatorDomain->unit_id = $unitId;
                                $indicatorDomain->level_id = $levelId;
                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unitId, $year);
                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                $indicatorDomain->parent_horizontal_id = null;
                                $indicatorDomain->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomain);

                                //target & realisasi 'CHILD' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $targetDomain->month = $validityKey;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = false;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain);

                                        $realizationDomain->id = (string) Str::orderedUuid();
                                        $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $realizationDomain->month = $validityKey;
                                        $realizationDomain->value = 0;
                                        $realizationDomain->locked = true;
                                        $realizationDomain->default = true;

                                        $this->realizationRepository->save($realizationDomain);
                                    }
                                }

                                //remove & replace KPI suspended
                                $temp = [];
                                $j = 0;
                                foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                    if ($indicatorSuspended !== $indicatorsIdSuspended[$i]) {
                                        $temp[$j] = $indicatorSuspended;
                                        $j++;
                                    }
                                }
                                $indicatorsIdSuspended = $temp;
                            } else {

                                $pathsNewIndicator = $this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]);

                                foreach ($pathsNewIndicator as $pathNewIndicator) {
                                    if (!is_null($pathNewIndicator)) {

                                        $oldIndicatorsChildOnlyCode = $this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unitId, $year);

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                        if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                            //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child'
                                            $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unitId, $year);

                                            if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) { //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child', tapi baru belum terdaftar di 'child'
                                                $indicatorDomain->id = $idListChild[$indicatorSuperMaster->id];
                                                $indicatorDomain->indicator = $indicatorSuperMaster->indicator;
                                                $indicatorDomain->formula = $indicatorSuperMaster->formula;
                                                $indicatorDomain->measure = $indicatorSuperMaster->measure;
                                                $indicatorDomain->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                $indicatorDomain->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                $indicatorDomain->year = $year;
                                                $indicatorDomain->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                $indicatorDomain->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                $indicatorDomain->reviewed = $indicatorSuperMaster->reviewed;
                                                $indicatorDomain->referenced = $indicatorSuperMaster->referenced;
                                                $indicatorDomain->dummy = $indicatorSuperMaster->dummy;
                                                $indicatorDomain->label = 'child';
                                                $indicatorDomain->unit_id = $unitId;
                                                $indicatorDomain->level_id = $levelId;
                                                $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unitId, $year);
                                                $indicatorDomain->code = $indicatorSuperMaster->code;
                                                $indicatorDomain->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                                $indicatorDomain->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unitId, $year);
                                                $indicatorDomain->created_by = $userId;

                                                $this->indicatorRepository->save($indicatorDomain);

                                                //target & realisasi 'CHILD' creating
                                                if (!is_null($indicatorSuperMaster->validity)) {
                                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                        $targetDomain->id = (string) Str::orderedUuid();
                                                        $targetDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                        $targetDomain->month = $validityKey;
                                                        $targetDomain->value = 0;
                                                        $targetDomain->locked = false;
                                                        $targetDomain->default = true;

                                                        $this->targetRepository->save($targetDomain);

                                                        $realizationDomain->id = (string) Str::orderedUuid();
                                                        $realizationDomain->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                        $realizationDomain->month = $validityKey;
                                                        $realizationDomain->value = 0;
                                                        $realizationDomain->locked = true;
                                                        $realizationDomain->default = true;

                                                        $this->realizationRepository->save($realizationDomain);
                                                    }
                                                }

                                                //remove & replace KPI suspended
                                                $temp = [];
                                                $j = 0;
                                                foreach ($indicatorsIdSuspended as $indicatorSuspended) {
                                                    if ($indicatorSuspended !== $pathNewIndicator) {
                                                        $temp[$j] = $indicatorSuspended;
                                                        $j++;
                                                    }
                                                }
                                                $indicatorsIdSuspended = $temp;
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                if (count($oldIndicatorsChild) > 0) { //terdapat 'id' KPI lama yang di un-checked.
                    foreach ($oldIndicatorsChild as $oldIndicatorChild) {
                        $this->targetRepository->delete__by__indicatorId($oldIndicatorChild); //target deleting
                        $this->realizationRepository->delete__by__indicatorId($oldIndicatorChild); //realisasi deleting
                        $this->indicatorRepository->delete__by__id($oldIndicatorChild); //KPI deleting
                    }
                }
                //end section: 'CHILD' updating ----------------------------------------------------------------------
            }
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository, TargetRepository, RealizationRepository
    public function destroy(IndicatorPaperWorkDestroyRequest $indicatorPaperWorkRequest): void
    {
        $level = $indicatorPaperWorkRequest->level;
        $unit = $indicatorPaperWorkRequest->unit;
        $year = $indicatorPaperWorkRequest->year;

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($level, $unit, $year) {
            $levelId = $this->levelRepository->find__id__by__slug($level);

            $indicators = $unit === 'master' ?
                $this->indicatorRepository->find__all__with__targets_realizations__by__levelId_unitId_year($levelId, null, $year) :
                $this->indicatorRepository->find__all__with__targets_realizations__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

            //target & realisasi deleting
            foreach ($indicators as $indicator) {
                foreach ($indicator->targets as $target) {
                    $this->targetRepository->delete__by__id($target->id);
                }

                foreach ($indicator->realizations as $realization) {
                    $this->realizationRepository->delete__by__id($realization->id);
                }
            }

            //KPI deleting
            $unit === 'master' ? $this->indicatorRepository->delete__by__levelId_unitId_year($this->levelRepository->find__id__by__slug($level), null, $year) : $this->indicatorRepository->delete__by__levelId_unitId_year($this->levelRepository->find__id__by__slug($level), $this->unitRepository->find__id__by__slug($unit), $year);
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    //use repo IndicatorRepository
    public function reorder(IndicatorPaperWorkReorderRequest $indicatorPaperWorkRequest): void
    {
        $indicators = $indicatorPaperWorkRequest->indicators;
        $level = $indicatorPaperWorkRequest->level;
        $unit = $indicatorPaperWorkRequest->unit;
        $year = $indicatorPaperWorkRequest->year;

        DB::transaction(function () use ($indicators, $level, $unit) {
            if ($level === 'super-master') {
                foreach ($indicators as $indicatorKey => $indicatorValue) {
                    $this->indicatorRepository->update__order__by__id($indicatorKey + 1, $indicatorValue); //'SUPER-MASTER' updating
                }
            } else {
                if ($unit === 'master') {
                    foreach ($indicators as $indicatorKey => $indicatorValue) {
                        $this->indicatorRepository->update__order__by__id($indicatorKey + 1, $indicatorValue); //'MASTER' updating
                        $this->indicatorRepository->update__order__by__parentVerticalId($indicatorKey + 1, $indicatorValue); //'CHILD' updating
                    }
                } else {
                    foreach ($indicators as $indicatorKey => $indicatorValue) {
                        $this->indicatorRepository->update__order__by__id($indicatorKey + 1, $indicatorValue); //'CHILD' updating
                    }
                }
            }
        });
    }
}
