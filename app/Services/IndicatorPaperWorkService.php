<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
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

    public function index(string|int $userId, string $level, ?string $unit, ?string $year) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->findWithRoleUnitLevelById($userId);

        $isSuperMaster = $level === 'super-master' ? true : false;
        $isSuperAdmin = $user->role->name === 'super-admin';
        $isSuperAdminOrAdmin = $isSuperAdmin || $user->role->name === 'admin';

        $currentLevelNotSameWithUserLevel = true;
        if ($user->role->name === 'super-admin') {
            $currentLevelNotSameWithUserLevel = true;
        } else {
            if ($level === $user->unit->level->slug) {
                $currentLevelNotSameWithUserLevel = false;
            } else {
                $currentLevelNotSameWithUserLevel = true;
            }
        }

        // 'permissions paper work indicator (create, edit, delete)' handler
        $numberOfChildLevel = $isSuperAdmin ? count(Arr::flatten($this->levelRepository->findAllSlugWithChildsByRoot())) : count(Arr::flatten($this->levelRepository->findAllSlugWithThisAndChildsById($user->unit->level->id)));

        $constructRequest = new ConstructRequest();

        $constructRequest->userRepository = $this->userRepository;
        $constructRequest->levelRepository = $this->levelRepository;

        $levelService = new LevelService($constructRequest);

        $response->levels = $levelService->levelsOfUser($userId, true);

        //$response->levels = $isSuperAdmin ? $this->levelRepository->findAllWithChildsByRoot() : $this->levelRepository->findAllWithChildsById($user->unit->level->id);

        $response->indicators = $this->indicatorRepository->findAllReferencedWithChildsByWhere(
            $level === 'super-master' ?
            ['label' => 'super-master'] :
            [
                'level_id' => $this->levelRepository->findIdBySlug($level),
                'label' => $unit === 'master' ? 'master' : 'child',
                'unit_id' => $unit === 'master' ? null : $this->unitRepository->findIdBySlug($unit),
                'year' => $year
            ]
        );

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

    public function create(string|int $userId) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->findWithRoleUnitLevelById($userId);

        $parentId = $user->role->name === 'super-admin' ? $this->levelRepository->findAllIdByRoot() : $this->levelRepository->findAllIdById($user->unit->level->id);

        $response->levels = $this->levelRepository->findAllWithChildsByParentIdArray(Arr::flatten($parentId));
        $response->indicators = $this->indicatorRepository->findAllReferencedWithChildsByWhere(['label' => 'super-master']);

        return $response;
    }

    public function store(array $indicators, string $level, string $year, string|int $userId) : void
    {
        DB::transaction(function () use ($indicators, $level, $year, $userId) {
            $levelId = $this->levelRepository->findIdBySlug($level);

            //cek indikator yang dipilih punya urutan sampai ke ROOT
            $familiesOfSelectedIndicator = [];
            foreach ($indicators as $key => $value) {
                $familiesOfSelectedIndicator = array_merge($familiesOfSelectedIndicator, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
            }

            $familiesIndicator = $this->indicatorRepository->findAllById(array_unique($familiesOfSelectedIndicator));

            $indicator = new Indicator();
            $target = new Target();
            $realization = new Realization();

            //section: paper work 'MASTER' creating

            //build ID
            $idListMaster = [];
            foreach ($familiesIndicator as $familyIndicator) {
                $idListMaster[$familyIndicator->id] = (string) Str::orderedUuid();
            }

            $i = 0;
            foreach ($familiesIndicator as $familyIndicator) {
                $indicator->id = $idListMaster[$familyIndicator->id];
                $indicator->indicator = $familyIndicator->indicator;
                $indicator->formula = $familyIndicator->formula;
                $indicator->measure = $familyIndicator->measure;
                $indicator->weight = $familyIndicator->getRawOriginal('weight');
                $indicator->polarity = $familyIndicator->getRawOriginal('polarity');
                $indicator->year = $year;
                $indicator->reducing_factor = $familyIndicator->reducing_factor;
                $indicator->validity = $familyIndicator->getRawOriginal('validity');
                $indicator->reviewed = $familyIndicator->reviewed;
                $indicator->referenced = $familyIndicator->referenced;
                $indicator->dummy = $familyIndicator->dummy;
                $indicator->label = 'master';
                $indicator->unit_id = null;
                $indicator->level_id = $levelId;
                $indicator->order = $i;
                $indicator->code = $familyIndicator->code;
                $indicator->parent_vertical_id = $familyIndicator->id;
                $indicator->parent_horizontal_id = is_null($familyIndicator->parent_horizontal_id) ? null : $idListMaster[$familyIndicator->parent_horizontal_id];
                $indicator->created_by = $userId;

                $this->indicatorRepository->save($indicator);

                //target 'MASTER' creating
                if (!is_null($familyIndicator->validity)) {
                    foreach ($familyIndicator->validity as $key => $value) {
                        $target->id = (string) Str::orderedUuid();
                        $target->indicator_id = $idListMaster[$familyIndicator->id];
                        $target->month = $key;
                        $target->value = 0;
                        $target->locked = true;
                        $target->default = true;

                        $this->targetRepository->save($target);
                    }
                }
                $i++;
            }
            //end section: paper work 'MASTER' creating

            //section: paper work 'CHILD' creating
            $units = $this->unitRepository->findAllByLevelId($levelId);
            $familiesIndicator = $this->indicatorRepository->findAllByWhere(['level_id' => $levelId, 'label' => 'master', 'year' => $year]);

            foreach ($units as $unit) {
                //build ID
                $idListChild = [];
                foreach ($familiesIndicator as $familyIndicator) {
                    $idListChild[$familyIndicator->id] = (string) Str::orderedUuid();
                }

                $i = 0;
                foreach ($familiesIndicator as $familyIndicator) {
                    $indicator->id = $idListChild[$familyIndicator->id];
                    $indicator->indicator = $familyIndicator->indicator;
                    $indicator->formula = $familyIndicator->formula;
                    $indicator->measure = $familyIndicator->measure;
                    $indicator->weight = $familyIndicator->getRawOriginal('weight');
                    $indicator->polarity = $familyIndicator->getRawOriginal('polarity');
                    $indicator->year = $year;
                    $indicator->reducing_factor = $familyIndicator->reducing_factor;
                    $indicator->validity = $familyIndicator->getRawOriginal('validity');
                    $indicator->reviewed = $familyIndicator->reviewed;
                    $indicator->referenced = $familyIndicator->referenced;
                    $indicator->dummy = $familyIndicator->dummy;
                    $indicator->label = 'child';
                    $indicator->unit_id = $unit->id;
                    $indicator->level_id = $levelId;
                    $indicator->order = $i;
                    $indicator->code = $familyIndicator->code;
                    $indicator->parent_vertical_id = $familyIndicator->id;
                    $indicator->parent_horizontal_id = is_null($familyIndicator->parent_horizontal_id) ? null : $idListChild[$familyIndicator->parent_horizontal_id];
                    $indicator->created_by = $userId;

                    $this->indicatorRepository->save($indicator);

                    //target & realization 'CHILD' creating
                    if (!is_null($familyIndicator->validity)) {
                        foreach ($familyIndicator->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $idListChild[$familyIndicator->id];
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target);

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $idListChild[$familyIndicator->id];
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization);
                        }
                    }
                    $i++;
                }
            }
            //end section: paper work 'CHILD' creating
        });
    }

    public function destroy(string $level, string $unit, string $year) : void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($level, $unit, $year) {
            $where = $unit === 'master' ? ['level_id' => $this->levelRepository->findIdBySlug($level), 'year' => $year] : ['level_id' => $this->levelRepository->findIdBySlug($level), 'unit_id' => $this->unitRepository->findIdBySlug($unit), 'year' => $year];

            $indicators = $this->indicatorRepository->findAllWithTargetsAndRealizationsByWhere($where);

            //deleting target & realization
            foreach ($indicators as $indicator) {
                foreach ($indicator->targets as $target) {
                    $this->targetRepository->deleteById($target->id);
                }

                foreach ($indicator->realizations as $realization) {
                    $this->realizationRepository->deleteById($realization->id);
                }
            }

            //deleting indicator
            $where = $unit === 'master' ? ['level_id' => $this->levelRepository->findIdBySlug($level), 'year' => $year] : ['level_id' => $this->levelRepository->findIdBySlug($level), 'unit_id' => $this->unitRepository->findIdBySlug($unit), 'year' => $year];

            $this->indicatorRepository->deleteByWhere($where);
        });
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
