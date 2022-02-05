<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorPaperWorkEditResponse;
use App\DTO\IndicatorPaperWorkIndexResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicatorPaperWorkService {
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
    public function index(string|int $userId, string $level, ?string $unit, ?string $year) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

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
        $numberOfChildLevel = $isSuperAdmin ? count(Arr::flatten($this->levelRepository->find__allSlug__with__childs__by__root())) : count(Arr::flatten($this->levelRepository->find__allSlug__with__this_childs__by__id($user->unit->level->id)));

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
    public function create(string|int $userId) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

        $parentId = $user->role->name === 'super-admin' ? $this->levelRepository->find__allId__by__root() : $this->levelRepository->find__allId__by__id($user->unit->level->id);

        $response->levels = $this->levelRepository->find__all__with__childs__by__parentIdList(Arr::flatten($parentId));
        $response->indicators = $this->indicatorRepository->find__allReferenced_rootHorizontal__with__childs__by__label_levelId_unitId_year('super-master', null, null, null);

        return $response;
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository, TargetRepository, RealizationRepository
    public function store(array $indicators, string $level, string $year, string|int $userId) : void
    {
        DB::transaction(function () use ($indicators, $level, $year, $userId) {
            $levelId = $this->levelRepository->find__id__by__slug($level);

            //membuat nasab KPI
            $pathsOfSelectedIndicator = [];
            foreach ($indicators as $value) {
                $pathsOfSelectedIndicator = array_merge($pathsOfSelectedIndicator, Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($value)));
            }

            //nasab KPI
            $pathsIndicator = $this->indicatorRepository->find__all__by__idList(array_unique($pathsOfSelectedIndicator));

            $indicator = new Indicator();
            $target = new Target();
            $realization = new Realization();

            //section: paper work 'MASTER' creating ----------------------------------------------------------------------

            //build ID
            $idListMaster = [];
            foreach ($pathsIndicator as $pathIndicator) {
                $idListMaster[$pathIndicator->id] = (string) Str::orderedUuid();
            }

            $i = 0;
            foreach ($pathsIndicator as $pathIndicator) {
                $indicator->id = $idListMaster[$pathIndicator->id];
                $indicator->indicator = $pathIndicator->indicator;
                $indicator->formula = $pathIndicator->formula;
                $indicator->measure = $pathIndicator->measure;
                $indicator->weight = $pathIndicator->getRawOriginal('weight');
                $indicator->polarity = $pathIndicator->getRawOriginal('polarity');
                $indicator->year = $year;
                $indicator->reducing_factor = $pathIndicator->reducing_factor;
                $indicator->validity = $pathIndicator->getRawOriginal('validity');
                $indicator->reviewed = $pathIndicator->reviewed;
                $indicator->referenced = $pathIndicator->referenced;
                $indicator->dummy = $pathIndicator->dummy;
                $indicator->label = 'master';
                $indicator->unit_id = null;
                $indicator->level_id = $levelId;
                $indicator->order = $i+1;
                $indicator->code = $pathIndicator->code;
                $indicator->parent_vertical_id = $pathIndicator->id;
                $indicator->parent_horizontal_id = is_null($pathIndicator->parent_horizontal_id) ? null : $idListMaster[$pathIndicator->parent_horizontal_id];
                $indicator->created_by = $userId;

                $this->indicatorRepository->save($indicator);

                //target 'MASTER' creating
                if (!is_null($pathIndicator->validity)) {
                    foreach ($pathIndicator->validity as $validityKey => $validityValue) {
                        $target->id = (string) Str::orderedUuid();
                        $target->indicator_id = $idListMaster[$pathIndicator->id];
                        $target->month = $validityKey;
                        $target->value = 0;
                        $target->locked = false;
                        $target->default = true;

                        $this->targetRepository->save($target);
                    }
                }
                $i++;
            }
            //end section: paper work 'MASTER' creating ----------------------------------------------------------------------

            //section: paper work 'CHILD' creating ----------------------------------------------------------------------
            $units = $this->unitRepository->find__all__by__levelId($levelId);
            $pathsIndicator = $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, null, $year);

            foreach ($units as $unit) {
                //build ID
                $idListChild = [];
                foreach ($pathsIndicator as $pathIndicator) {
                    $idListChild[$pathIndicator->id] = (string) Str::orderedUuid();
                }

                $i = 0;
                foreach ($pathsIndicator as $pathIndicator) {
                    $indicator->id = $idListChild[$pathIndicator->id];
                    $indicator->indicator = $pathIndicator->indicator;
                    $indicator->formula = $pathIndicator->formula;
                    $indicator->measure = $pathIndicator->measure;
                    $indicator->weight = $pathIndicator->getRawOriginal('weight');
                    $indicator->polarity = $pathIndicator->getRawOriginal('polarity');
                    $indicator->year = $year;
                    $indicator->reducing_factor = $pathIndicator->reducing_factor;
                    $indicator->validity = $pathIndicator->getRawOriginal('validity');
                    $indicator->reviewed = $pathIndicator->reviewed;
                    $indicator->referenced = $pathIndicator->referenced;
                    $indicator->dummy = $pathIndicator->dummy;
                    $indicator->label = 'child';
                    $indicator->unit_id = $unit->id;
                    $indicator->level_id = $levelId;
                    $indicator->order = $i+1;
                    $indicator->code = $pathIndicator->code;
                    $indicator->parent_vertical_id = $pathIndicator->id;
                    $indicator->parent_horizontal_id = is_null($pathIndicator->parent_horizontal_id) ? null : $idListChild[$pathIndicator->parent_horizontal_id];
                    $indicator->created_by = $userId;

                    $this->indicatorRepository->save($indicator);

                    //target & realisasi 'CHILD' creating
                    if (!is_null($pathIndicator->validity)) {
                        foreach ($pathIndicator->validity as $validityKey => $validityValue) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $idListChild[$pathIndicator->id];
                            $target->month = $validityKey;
                            $target->value = 0;
                            $target->locked = false;
                            $target->default = true;

                            $this->targetRepository->save($target);

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $idListChild[$pathIndicator->id];
                            $realization->month = $validityKey;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization);
                        }
                    }
                    $i++;
                }
            }
            //end section: paper work 'CHILD' creating ----------------------------------------------------------------------
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function edit(string $level, string $unit, string $year) : IndicatorPaperWorkEditResponse
    {
        $response = new IndicatorPaperWorkEditResponse;

        $response->super_master_indicators = $this->indicatorRepository->find__all__with__childs_referenced__by__superMasterLabel();

        $levelId = $this->levelRepository->find__id__by__slug($level);

        $response->indicators = $unit === 'master' ? $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, null, $year) : $this->indicatorRepository->find__all__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository, RealizationRepository, UserRepository
    public function update(array $indicatorsFromInput, string $level, string $unit, string $year, string|int $userId) : void
    {
        $indicatorDomains = new Indicator();
        $targetDomains = new Target();
        $realizationDomains = new Realization();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($indicatorsFromInput, $level, $unit, $year, $userId, $indicatorDomains, $targetDomains, $realizationDomains) {
            $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

            $levelId = $this->levelRepository->find__id__by__slug($level);
            $unitId = $unit === 'master' ? null : $this->unitRepository->find__id__by__slug($unit);

            if ($unit === 'master') {
                //section: 'MASTER' updating ----------------------------------------------------------------------

                //daftar 'id' dari KPI-KPI 'master'
                $oldIndicatorsMasterOnlyId = Arr::flatten($this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $year));

                //daftar 'code' dari KPI-KPI 'master'
                $oldIndicatorsMasterOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year));

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
                        $pathsNewIndicators = array_merge($pathsNewIndicators, Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInMaster)));
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

                            $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator));

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

                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomains->label = 'master';
                                $indicatorDomains->unit_id = null;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityvalue) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomains->month = $validityKey;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = false;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);
                                    }
                                }
                            } else {
                                $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                $i++;
                            }
                        }
                    }

                    while (count($indicatorsIdSuspended) > 0) {
                        for ($i=0; $i < count($indicatorsIdSuspended); $i++) {

                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                            if (is_null($indicatorSuperMaster->parent_horizontal_id)) {
                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomains->label = 'master';
                                $indicatorDomains->unit_id = null;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = null;
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomains->month = $validityKey;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = false;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);
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
                                $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]));

                                foreach ($pathsNewIndicator as $pathNewIndicator) {
                                    if (!is_null($pathNewIndicator)) {

                                        $oldIndicatorsMasterOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year));

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                        if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                            //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'master'
                                            $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);

                                            if (($sum > 0) && !in_array($indicatorSuperMaster->id, $oldIndicatorsMasterOnlyCode)) { //parent_horizontal_id KPI baru sudah terdaftar di master, tapi KPI baru bukan anggota KPI lama
                                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                $indicatorDomains->year = $year;
                                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                                $indicatorDomains->label = 'master';
                                                $indicatorDomains->unit_id = null;
                                                $indicatorDomains->level_id = $levelId;
                                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                                $indicatorDomains->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                                $indicatorDomains->created_by = $userId;

                                                $this->indicatorRepository->save($indicatorDomains);

                                                //target 'MASTER' creating
                                                if (!is_null($indicatorSuperMaster->validity)) {
                                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                        $targetDomains->id = (string) Str::orderedUuid();
                                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                                        $targetDomains->month = $validityKey;
                                                        $targetDomains->value = 0;
                                                        $targetDomains->locked = false;
                                                        $targetDomains->default = true;

                                                        $this->targetRepository->save($targetDomains);
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
                $oldIndicatorsMasterOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year));

                //daftar 'id' dari KPI-KPI 'master' yang di un-checked
                $oldIndicatorsMasterUnchecked = $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($oldIndicatorsMaster, $levelId, null, $year);

                //jika role user adalah 'super-admin' maka implementasi diterapkan pada semua unit, selain itu hanya unit turunannya
                if ($user->role->name === 'super-admin') {
                    foreach ($units as $unit) {
                        //daftar 'id' dari KPI-KPI 'child'
                        $oldIndicatorsChildOnlyId = Arr::flatten($this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $unit->id, $year));

                        //daftar 'code' dari KPI-KPI 'child'
                        $oldIndicatorsChildOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year));

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
                                $pathsNewIndicators = array_merge($pathsNewIndicators, Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInChild)));
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

                                    $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator));

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

                                        $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                        $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                        $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                        $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                        $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                        $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                        $indicatorDomains->year = $year;
                                        $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                        $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                        $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                        $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                        $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                        $indicatorDomains->label = 'child';
                                        $indicatorDomains->unit_id = $unit->id;
                                        $indicatorDomains->level_id = $levelId;
                                        $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                        $indicatorDomains->code = $indicatorSuperMaster->code;
                                        $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                        $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                        $indicatorDomains->created_by = $userId;

                                        $this->indicatorRepository->save($indicatorDomains);

                                        //target & realisasi 'CHILD' creating
                                        if (!is_null($indicatorSuperMaster->validity)) {
                                            foreach ($indicatorSuperMaster->validity as $validitykey => $validityValue) {
                                                $targetDomains->id = (string) Str::orderedUuid();
                                                $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $targetDomains->month = $validitykey;
                                                $targetDomains->value = 0;
                                                $targetDomains->locked = false;
                                                $targetDomains->default = true;

                                                $this->targetRepository->save($targetDomains);

                                                $realizationDomains->id = (string) Str::orderedUuid();
                                                $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $realizationDomains->month = $validitykey;
                                                $realizationDomains->value = 0;
                                                $realizationDomains->locked = true;
                                                $realizationDomains->default = true;

                                                $this->realizationRepository->save($realizationDomains);
                                            }
                                        }
                                    } else {
                                        $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                        $i++;
                                    }
                                }
                            }

                            while (count($indicatorsIdSuspended) > 0) {
                                for ($i=0; $i < count($indicatorsIdSuspended); $i++) {

                                    $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                                    if ($indicatorSuperMaster->parent_horizontal_id === null) {
                                        $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                        $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                        $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                        $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                        $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                        $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                        $indicatorDomains->year = $year;
                                        $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                        $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                        $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                        $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                        $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                        $indicatorDomains->label = 'child';
                                        $indicatorDomains->unit_id = $unit->id;
                                        $indicatorDomains->level_id = $levelId;
                                        $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                        $indicatorDomains->code = $indicatorSuperMaster->code;
                                        $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                        $indicatorDomains->parent_horizontal_id = null;
                                        $indicatorDomains->created_by = $userId;

                                        $this->indicatorRepository->save($indicatorDomains);

                                        //target & realisasi 'CHILD' creating
                                        if (!is_null($indicatorSuperMaster->validity)) {
                                            foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                $targetDomains->id = (string) Str::orderedUuid();
                                                $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $targetDomains->month = $validityKey;
                                                $targetDomains->value = 0;
                                                $targetDomains->locked = false;
                                                $targetDomains->default = true;

                                                $this->targetRepository->save($targetDomains);

                                                $realizationDomains->id = (string) Str::orderedUuid();
                                                $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $realizationDomains->month = $validityKey;
                                                $realizationDomains->value = 0;
                                                $realizationDomains->locked = true;
                                                $realizationDomains->default = true;

                                                $this->realizationRepository->save($realizationDomains);
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

                                        $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]));

                                        foreach ($pathsNewIndicator as $pathNewIndicator) {
                                            if (!is_null($pathNewIndicator)) {

                                                $oldIndicatorsChildOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year));

                                                $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                                if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                                    //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child'
                                                    $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);

                                                    if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) { //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child', tapi baru belum terdaftar di 'child'
                                                        $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                                        $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                                        $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                                        $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                                        $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                        $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                        $indicatorDomains->year = $year;
                                                        $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                        $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                        $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                                        $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                                        $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                                        $indicatorDomains->label = 'child';
                                                        $indicatorDomains->unit_id = $unit->id;
                                                        $indicatorDomains->level_id = $levelId;
                                                        $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                                        $indicatorDomains->code = $indicatorSuperMaster->code;
                                                        $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                                        $indicatorDomains->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                                        $indicatorDomains->created_by = $userId;

                                                        $this->indicatorRepository->save($indicatorDomains);

                                                        //target & realisasi 'CHILD' creating
                                                        if (!is_null($indicatorSuperMaster->validity)) {
                                                            foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                                $targetDomains->id = (string) Str::orderedUuid();
                                                                $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                $targetDomains->month = $validityKey;
                                                                $targetDomains->value = 0;
                                                                $targetDomains->locked = false;
                                                                $targetDomains->default = true;

                                                                $this->targetRepository->save($targetDomains);

                                                                $realizationDomains->id = (string) Str::orderedUuid();
                                                                $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                $realizationDomains->month = $validityKey;
                                                                $realizationDomains->value = 0;
                                                                $realizationDomains->locked = true;
                                                                $realizationDomains->default = true;

                                                                $this->realizationRepository->save($realizationDomains);
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
                    $unitsId = Arr::flatten($this->unitRepository->find__allId__with__childs__by__id($user->unit->id)); //mengambil daftar 'id' unit-unit turunan berdasarkan user

                    foreach ($units as $unit) {
                        if (in_array($unit->id, $unitsId)) {
                            //daftar 'id' dari KPI-KPI 'child'
                            $oldIndicatorsChildOnlyId = Arr::flatten($this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $unit->id, $year));

                            //daftar 'code' dari KPI-KPI 'child'
                            $oldIndicatorsChildOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year));

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
                                    $pathsNewIndicators = array_merge($pathsNewIndicators, Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInChild)));
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

                                        $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator));

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

                                            $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                            $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                            $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                            $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                            $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                            $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                            $indicatorDomains->year = $year;
                                            $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                            $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                            $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                            $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                            $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                            $indicatorDomains->label = 'child';
                                            $indicatorDomains->unit_id = $unit->id;
                                            $indicatorDomains->level_id = $levelId;
                                            $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                            $indicatorDomains->code = $indicatorSuperMaster->code;
                                            $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                            $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                            $indicatorDomains->created_by = $userId;

                                            $this->indicatorRepository->save($indicatorDomains);

                                            //target & realisasi 'CHILD' creating
                                            if (!is_null($indicatorSuperMaster->validity)) {
                                                foreach ($indicatorSuperMaster->validity as $validitykey => $validityValue) {
                                                    $targetDomains->id = (string) Str::orderedUuid();
                                                    $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $targetDomains->month = $validitykey;
                                                    $targetDomains->value = 0;
                                                    $targetDomains->locked = false;
                                                    $targetDomains->default = true;

                                                    $this->targetRepository->save($targetDomains);

                                                    $realizationDomains->id = (string) Str::orderedUuid();
                                                    $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $realizationDomains->month = $validitykey;
                                                    $realizationDomains->value = 0;
                                                    $realizationDomains->locked = true;
                                                    $realizationDomains->default = true;

                                                    $this->realizationRepository->save($realizationDomains);
                                                }
                                            }
                                        } else {
                                            $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                            $i++;
                                        }
                                    }
                                }

                                while (count($indicatorsIdSuspended) > 0) {
                                    for ($i=0; $i < count($indicatorsIdSuspended); $i++) {

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                                        if ($indicatorSuperMaster->parent_horizontal_id === null) {
                                            $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                            $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                            $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                            $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                            $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                            $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                            $indicatorDomains->year = $year;
                                            $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                            $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                            $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                            $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                            $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                            $indicatorDomains->label = 'child';
                                            $indicatorDomains->unit_id = $unit->id;
                                            $indicatorDomains->level_id = $levelId;
                                            $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                            $indicatorDomains->code = $indicatorSuperMaster->code;
                                            $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                            $indicatorDomains->parent_horizontal_id = null;
                                            $indicatorDomains->created_by = $userId;

                                            $this->indicatorRepository->save($indicatorDomains);

                                            //target & realisasi 'CHILD' creating
                                            if (!is_null($indicatorSuperMaster->validity)) {
                                                foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                    $targetDomains->id = (string) Str::orderedUuid();
                                                    $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $targetDomains->month = $validityKey;
                                                    $targetDomains->value = 0;
                                                    $targetDomains->locked = false;
                                                    $targetDomains->default = true;

                                                    $this->targetRepository->save($targetDomains);

                                                    $realizationDomains->id = (string) Str::orderedUuid();
                                                    $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $realizationDomains->month = $validityKey;
                                                    $realizationDomains->value = 0;
                                                    $realizationDomains->locked = true;
                                                    $realizationDomains->default = true;

                                                    $this->realizationRepository->save($realizationDomains);
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

                                            $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]));

                                            foreach ($pathsNewIndicator as $pathNewIndicator) {
                                                if (!is_null($pathNewIndicator)) {

                                                    $oldIndicatorsChildOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unit->id, $year));

                                                    $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                                    if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                                        //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child'
                                                        $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);

                                                        if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) { //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child', tapi baru belum terdaftar di 'child'
                                                            $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                                            $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                                            $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                                            $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                                            $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                            $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                            $indicatorDomains->year = $year;
                                                            $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                            $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                            $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                                            $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                                            $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                                            $indicatorDomains->label = 'child';
                                                            $indicatorDomains->unit_id = $unit->id;
                                                            $indicatorDomains->level_id = $levelId;
                                                            $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unit->id, $year);
                                                            $indicatorDomains->code = $indicatorSuperMaster->code;
                                                            $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                                            $indicatorDomains->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unit->id, $year);
                                                            $indicatorDomains->created_by = $userId;

                                                            $this->indicatorRepository->save($indicatorDomains);

                                                            //target & realisasi 'CHILD' creating
                                                            if (!is_null($indicatorSuperMaster->validity)) {
                                                                foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                                    $targetDomains->id = (string) Str::orderedUuid();
                                                                    $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                    $targetDomains->month = $validityKey;
                                                                    $targetDomains->value = 0;
                                                                    $targetDomains->locked = false;
                                                                    $targetDomains->default = true;

                                                                    $this->targetRepository->save($targetDomains);

                                                                    $realizationDomains->id = (string) Str::orderedUuid();
                                                                    $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                                    $realizationDomains->month = $validityKey;
                                                                    $realizationDomains->value = 0;
                                                                    $realizationDomains->locked = true;
                                                                    $realizationDomains->default = true;

                                                                    $this->realizationRepository->save($realizationDomains);
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
                $oldIndicatorsChildOnlyId = Arr::flatten($this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $unitId, $year));

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
                $oldIndicatorsMasterOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year));

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
                        $pathsNewIndicators = array_merge($pathsNewIndicators, Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInMaster)));
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

                            $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator));

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

                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomains->label = 'master';
                                $indicatorDomains->unit_id = null;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validitykey => $validityvalue) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomains->month = $validitykey;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = false;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);
                                    }
                                }
                            } else {
                                $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                $i++;
                            }
                        }
                    }

                    while (count($indicatorsIdSuspended) > 0) {
                        for ($i=0; $i < count($indicatorsIdSuspended); $i++) {

                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                            if (is_null($indicatorSuperMaster->parent_horizontal_id)) {
                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomains->label = 'master';
                                $indicatorDomains->unit_id = null;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = null;
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomains->month = $validityKey;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = false;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);
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
                                $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]));

                                foreach ($pathsNewIndicator as $pathNewIndicator) {
                                    if (!is_null($pathNewIndicator)) {

                                        $oldIndicatorsMasterOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, null, $year));

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                        if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                            //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'master'
                                            $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);

                                            if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsMasterOnlyCode)) { //parent_horizontal_id KPI baru sudah terdaftar di master, tapi KPI baru bukan anggota KPI lama
                                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                $indicatorDomains->year = $year;
                                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                                $indicatorDomains->label = 'master';
                                                $indicatorDomains->unit_id = null;
                                                $indicatorDomains->level_id = $levelId;
                                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, null, $year);
                                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                                $indicatorDomains->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, null, $year);
                                                $indicatorDomains->created_by = $userId;

                                                $this->indicatorRepository->save($indicatorDomains);

                                                //target 'MASTER' creating
                                                if (!is_null($indicatorSuperMaster->validity)) {
                                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                        $targetDomains->id = (string) Str::orderedUuid();
                                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                                        $targetDomains->month = $validityKey;
                                                        $targetDomains->value = 0;
                                                        $targetDomains->locked = false;
                                                        $targetDomains->default = true;

                                                        $this->targetRepository->save($targetDomains);
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
                $oldIndicatorsChildOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unitId, $year));

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
                        $pathsNewIndicators = array_merge($pathsNewIndicators, Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($newIndicatorNotExisInChild)));
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

                            $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($mergePathNewAndOldIndicator));

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

                                $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomains->label = 'child';
                                $indicatorDomains->unit_id = $unitId;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unitId, $year);
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unitId, $year);
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target & realisasi 'CHILD' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validitykey => $validityValue) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $targetDomains->month = $validitykey;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = false;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);

                                        $realizationDomains->id = (string) Str::orderedUuid();
                                        $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $realizationDomains->month = $validitykey;
                                        $realizationDomains->value = 0;
                                        $realizationDomains->locked = true;
                                        $realizationDomains->default = true;

                                        $this->realizationRepository->save($realizationDomains);
                                    }
                                }
                            } else {
                                $indicatorsIdSuspended[$i] = $mergePathNewAndOldIndicator;
                                $i++;
                            }
                        }
                    }

                    while (count($indicatorsIdSuspended) > 0) {
                        for ($i=0; $i < count($indicatorsIdSuspended); $i++) {

                            $indicatorSuperMaster = $this->indicatorRepository->find__by__id($indicatorsIdSuspended[$i]); //get KPI 'super-master' by id

                            if ($indicatorSuperMaster->parent_horizontal_id === null) {
                                $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                $indicatorDomains->label = 'child';
                                $indicatorDomains->unit_id = $unitId;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unitId, $year);
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                $indicatorDomains->parent_horizontal_id = null;
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target & realisasi 'CHILD' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $targetDomains->month = $validityKey;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = false;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);

                                        $realizationDomains->id = (string) Str::orderedUuid();
                                        $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                        $realizationDomains->month = $validityKey;
                                        $realizationDomains->value = 0;
                                        $realizationDomains->locked = true;
                                        $realizationDomains->default = true;

                                        $this->realizationRepository->save($realizationDomains);
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

                                $pathsNewIndicator = Arr::flatten($this->indicatorRepository->find__all__with__parents__by__id($indicatorsIdSuspended[$i]));

                                foreach ($pathsNewIndicator as $pathNewIndicator) {
                                    if (!is_null($pathNewIndicator)) {

                                        $oldIndicatorsChildOnlyCode = Arr::flatten($this->indicatorRepository->find__allCode__by__levelId_unitId_year($levelId, $unitId, $year));

                                        $indicatorSuperMaster = $this->indicatorRepository->find__by__id($pathNewIndicator); //get KPI 'super-master' by id

                                        if (!is_null($indicatorSuperMaster->parent_horizontal_id)) {

                                            //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child'
                                            $sum = $this->indicatorRepository->count__all__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unitId, $year);

                                            if (($sum > 0) && !in_array($pathNewIndicator, $oldIndicatorsChildOnlyCode)) { //'code' dengan 'parent_horizontal_id' X sudah tersedia di 'child', tapi baru belum terdaftar di 'child'
                                                $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                                $indicatorDomains->indicator = $indicatorSuperMaster->indicator;
                                                $indicatorDomains->formula = $indicatorSuperMaster->formula;
                                                $indicatorDomains->measure = $indicatorSuperMaster->measure;
                                                $indicatorDomains->weight = $indicatorSuperMaster->getRawOriginal('weight');
                                                $indicatorDomains->polarity = $indicatorSuperMaster->getRawOriginal('polarity');
                                                $indicatorDomains->year = $year;
                                                $indicatorDomains->reducing_factor = $indicatorSuperMaster->reducing_factor;
                                                $indicatorDomains->validity = $indicatorSuperMaster->getRawOriginal('validity');
                                                $indicatorDomains->reviewed = $indicatorSuperMaster->reviewed;
                                                $indicatorDomains->referenced = $indicatorSuperMaster->referenced;
                                                $indicatorDomains->dummy = $indicatorSuperMaster->dummy;
                                                $indicatorDomains->label = 'child';
                                                $indicatorDomains->unit_id = $unitId;
                                                $indicatorDomains->level_id = $levelId;
                                                $indicatorDomains->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year($levelId, $unitId, $year);
                                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                                $indicatorDomains->parent_vertical_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->id, $levelId, null, $year);
                                                $indicatorDomains->parent_horizontal_id = $this->indicatorRepository->find__id__by__code_levelId_unitId_year($indicatorSuperMaster->parent_horizontal_id, $levelId, $unitId, $year);
                                                $indicatorDomains->created_by = $userId;

                                                $this->indicatorRepository->save($indicatorDomains);

                                                //target & realisasi 'CHILD' creating
                                                if (!is_null($indicatorSuperMaster->validity)) {
                                                    foreach ($indicatorSuperMaster->validity as $validityKey => $validityValue) {
                                                        $targetDomains->id = (string) Str::orderedUuid();
                                                        $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                        $targetDomains->month = $validityKey;
                                                        $targetDomains->value = 0;
                                                        $targetDomains->locked = false;
                                                        $targetDomains->default = true;

                                                        $this->targetRepository->save($targetDomains);

                                                        $realizationDomains->id = (string) Str::orderedUuid();
                                                        $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                        $realizationDomains->month = $validityKey;
                                                        $realizationDomains->value = 0;
                                                        $realizationDomains->locked = true;
                                                        $realizationDomains->default = true;

                                                        $this->realizationRepository->save($realizationDomains);
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
    public function destroy(string $level, string $unit, string $year) : void
    {
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
    public function reorder(array $indicators, string $level, ?string $unit, ?string $year) : void
    {
        DB::transaction(function () use ($indicators, $level, $unit) {
            if ($level === 'super-master') {
                foreach ($indicators as $indicatorKey => $indicatorValue) {
                    $this->indicatorRepository->update__order__by__id($indicatorKey+1, $indicatorValue); //'SUPER-MASTER' updating
                }
            } else {
                if ($unit === 'master') {
                    foreach ($indicators as $indicatorKey => $indicatorValue) {
                        $this->indicatorRepository->update__order__by__id($indicatorKey+1, $indicatorValue); //'MASTER' updating
                        $this->indicatorRepository->update__order__by__parentVerticalId($indicatorKey+1, $indicatorValue); //'CHILD' updating
                    }
                } else {
                    foreach ($indicators as $indicatorKey => $indicatorValue) {
                        $this->indicatorRepository->update__order__by__id($indicatorKey+1, $indicatorValue); //'CHILD' updating
                    }
                }
            }
        });
    }
}
