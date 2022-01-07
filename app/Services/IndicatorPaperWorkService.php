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

    //use repo IndicatorRepository, LevelRepository, UserRepository
    public function create(string|int $userId) : IndicatorPaperWorkIndexResponse
    {
        $response = new IndicatorPaperWorkIndexResponse();

        $user = $this->userRepository->findWithRoleUnitLevelById($userId);

        $parentId = $user->role->name === 'super-admin' ? $this->levelRepository->findAllIdByRoot() : $this->levelRepository->findAllIdById($user->unit->level->id);

        $response->levels = $this->levelRepository->findAllWithChildsByParentIdList(Arr::flatten($parentId));
        $response->indicators = $this->indicatorRepository->findAllReferencedWithChildsByWhere(['label' => 'super-master']);

        return $response;
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository, TargetRepository, RealizationRepository
    public function store(array $indicators, string $level, string $year, string|int $userId) : void
    {
        DB::transaction(function () use ($indicators, $level, $year, $userId) {
            $levelId = $this->levelRepository->findIdBySlug($level);

            //membuat nasab indikator
            $pathsOfSelectedIndicator = [];
            foreach ($indicators as $value) {
                $pathsOfSelectedIndicator = array_merge($pathsOfSelectedIndicator, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
            }

            //nasab indikator
            $pathsIndicator = $this->indicatorRepository->findAllByIdList(array_unique($pathsOfSelectedIndicator));

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
                    foreach ($pathIndicator->validity as $key => $value) {
                        $target->id = (string) Str::orderedUuid();
                        $target->indicator_id = $idListMaster[$pathIndicator->id];
                        $target->month = $key;
                        $target->value = 0;
                        $target->locked = true;
                        $target->default = true;

                        $this->targetRepository->save($target);
                    }
                }
                $i++;
            }
            //end section: paper work 'MASTER' creating ----------------------------------------------------------------------

            //section: paper work 'CHILD' creating ----------------------------------------------------------------------
            $units = $this->unitRepository->findAllByLevelId($levelId);
            $pathsIndicator = $this->indicatorRepository->findAllByWhere(['level_id' => $levelId, 'label' => 'master', 'year' => $year]);

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

                    //target & realization 'CHILD' creating
                    if (!is_null($pathIndicator->validity)) {
                        foreach ($pathIndicator->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $idListChild[$pathIndicator->id];
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target);

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $idListChild[$pathIndicator->id];
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
            //end section: paper work 'CHILD' creating ----------------------------------------------------------------------
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function edit(string $level, string $unit, string $year) : IndicatorPaperWorkEditResponse
    {
        $response = new IndicatorPaperWorkEditResponse;

        $response->super_master_indicators = $this->indicatorRepository->findAllReferencedBySuperMasterLabel();

        $levelId = $this->levelRepository->findIdBySlug($level);

        $response->indicators = $unit === 'master' ? $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYear($levelId, null, $year) : $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($unit), $year);

        return $response;
    }

    //use repo LevelRepository, UnitRepository, IndicatorRepository, TargetRepository, RealizationRepository
    public function update(array $indicatorsFromInput, string $level, string $unit, string $year, string|int $userId) : void
    {
        $indicatorDomains = new Indicator();
        $targetDomains = new Target();
        $realizationDomains = new Realization();

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::transaction(function () use ($indicatorsFromInput, $level, $unit, $year, $userId, $indicatorDomains, $targetDomains, $realizationDomains) {

            //logging.
            $output = new \Symfony\Component\Console\Output\ConsoleOutput();

            $output->writeln('--------------------------------');
            $output->writeln(sprintf('indicators: %s', json_encode($indicatorsFromInput)));
            $output->writeln('--------------------------------');

            $levelId = $this->levelRepository->findIdBySlug($level);
            $unitId = $unit === 'master' ? null : $this->unitRepository->findIdBySlug($unit);

            if ($unit === 'master') {

                //mengambil daftar 'id' indikator lama sesuai 'level_id', 'unit_id' & 'year'.
                $indicatorsOldOnlyIdForMaster = Arr::flatten($this->indicatorRepository->findAllIdIsByLevelIdAndUnitIdAndYear($levelId, $unitId, $year));

                //mencari selisih 'id' indikator dengan cara membandingkan 'id' dari indikator request dengan 'id' dari indikator lama.
                //kemudian, selisih 'id' indikator yang diperoleh diasumsikan sebagai 'id' indikator baru.
                $newForMaster = [];
                $i = 0;
                foreach ($indicatorsFromInput as $value) {
                    if (!in_array($value, $indicatorsOldOnlyIdForMaster)) {
                        $newForMaster[$i] = $value;
                        $i++;
                    }
                }

                //mencari selisih 'id' indikator dengan cara membandingkan 'id' dari indikator lama dengan 'id' dari indikator request.
                //kemudian, selisih 'id' indikator yang diperoleh diasumsikan sebagai 'id' indikator lama.
                $oldForMaster = [];
                $i = 0;
                foreach ($indicatorsOldOnlyIdForMaster as $value) {
                    if (!in_array($value, $indicatorsFromInput)) {
                        $oldForMaster[$i] = $value;
                        $i++;
                    }
                }

                if (count($newForMaster) > 0) { //terdapat 'id' indikator baru.

                    //nasab 'id' indikator baru.
                    //nasab berupa daftar 'id' indikator yang mencerminkan jalur keturunan ke atas dari 'id' indikator baru.
                    $familiesOfIndicatorNewForMaster = [];
                    foreach ($newForMaster as $value) {
                        $familiesOfIndicatorNewForMaster = array_merge($familiesOfIndicatorNewForMaster, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
                    }

                    //daftar 'code' dari 'id' indikator lama sesuai 'level_id', 'unit_id' & 'year' yang masih di checked.
                    //daftar 'code' mencerminkan 'id' indikator pada 'super-master'.
                    $indicatorsOnlyIdAndCodeForMaster = $this->indicatorRepository->findAllCodeByLevelIdAndUnitIdAndYear($levelId, $unitId, $year);
                    $codesIndicatorOldForMaster = [];
                    $i = 0;
                    foreach ($indicatorsOnlyIdAndCodeForMaster as $indicatorOnlyIdAndCode) {
                        if (!in_array($indicatorOnlyIdAndCode->id, $oldForMaster)) { //seleksi 'id' indikator lama yang masih di checked.
                            $codesIndicatorOldForMaster[$i] = $indicatorOnlyIdAndCode->code;
                            $i++;
                        }
                    }

                    //gabungan 'id' indikator lama yang masih di checked & 'id' indikator baru.
                    $familiesIndicatorForMaster = array_unique(array_merge($familiesOfIndicatorNewForMaster, $codesIndicatorOldForMaster));

                    //section: paper work 'MASTER' updating ----------------------------------------------------------------------

                    //build ID
                    $idListMaster = [];
                    foreach ($familiesIndicatorForMaster as $familyId) {
                        if (!is_null($familyId)) {
                            $idListMaster[$familyId] = (string) Str::orderedUuid();
                        }
                    }

                    $i = 0;
                    foreach ($familiesIndicatorForMaster as $familyId) {
                        if (!is_null($familyId)) { //mencegah array gabungan 'id' indikator master & 'id' indikator baru yang belum terdaftar di 'master' item-nya ada null

                            $indicatorSuperMaster = $this->indicatorRepository->findById($familyId); //indikator dari 'super-master'

                            if (in_array($familyId, $codesIndicatorOldForMaster)) {
                                $indicatorOld = $this->indicatorRepository->findAllByCodeAndLevelIdAndUnitIdAndYear($familyId, $levelId, $unitId, $year); //indikator dari 'master'

                                //salin indikator lama
                                $indicatorDomains->id = $idListMaster[$indicatorSuperMaster->id];
                                $indicatorDomains->indicator = $indicatorOld->indicator;
                                $indicatorDomains->formula = $indicatorOld->formula;
                                $indicatorDomains->measure = $indicatorOld->measure;
                                $indicatorDomains->weight = $indicatorOld->getRawOriginal('weight');
                                $indicatorDomains->polarity = $indicatorOld->getRawOriginal('polarity');
                                $indicatorDomains->year = $year;
                                $indicatorDomains->reducing_factor = $indicatorOld->reducing_factor;
                                $indicatorDomains->validity = $indicatorOld->getRawOriginal('validity');
                                $indicatorDomains->reviewed = $indicatorOld->reviewed;
                                $indicatorDomains->referenced = $indicatorOld->referenced;
                                $indicatorDomains->dummy = $indicatorOld->dummy;
                                $indicatorDomains->label = 'master';
                                $indicatorDomains->unit_id = null;
                                $indicatorDomains->level_id = $levelId;
                                $indicatorDomains->order = $i+1;
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $idListMaster[$indicatorSuperMaster->parent_horizontal_id];
                                $indicatorDomains->created_by = $indicatorOld->created_by;

                                $this->indicatorRepository->save($indicatorDomains); //membuat indikator baru

                                if (!$indicatorOld->dummy) {
                                    //target 'MASTER' creating
                                    if (!is_null($indicatorOld->validity)) {
                                        foreach ($indicatorOld->validity as $key => $value) {
                                            $targetDomains->id = (string) Str::orderedUuid();
                                            $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                            $targetDomains->month = $key;
                                            $targetDomains->value = 0;
                                            $targetDomains->locked = true;
                                            $targetDomains->default = true;

                                            $this->targetRepository->save($targetDomains); //membuat target baru
                                        }
                                    }
                                }

                                foreach ($indicatorOld->targets as $target) {
                                    $this->targetRepository->deleteById($target->id); //hapus target lama
                                }

                                $this->indicatorRepository->deleteById($indicatorOld->id); //hapus indikator lama
                            } else {
                                //buat baru
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
                                $indicatorDomains->order = $i+1;
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $idListMaster[$indicatorSuperMaster->parent_horizontal_id];
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $key => $value) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomains->month = $key;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = true;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);
                                    }
                                }
                            }
                            $i++;
                        }
                    }
                    //end section: paper work 'MASTER' updating ----------------------------------------------------------------------

                    //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    $units = $this->unitRepository->findAllByLevelId($levelId);

                    foreach ($units as $unit) {

                        //mengambil daftar 'id' indikator lama sesuai 'level_id', 'unit_id' & 'year'.
                        $indicatorsOldOnlyIdForChild = Arr::flatten($this->indicatorRepository->findAllIdIsByLevelIdAndUnitIdAndYear($levelId, $unit->id, $year));

                        //gabungan daftar 'id' indikator lama milik 'master' & daftar 'id' indikator lama milik 'chlid'
                        $indicatorsOldMasterAndChild = array_merge($indicatorsOldOnlyIdForMaster, $indicatorsOldOnlyIdForChild);

                        //mencari selisih 'id' indikator dengan cara membandingkan 'id' dari indikator request dengan 'id' dari indikator lama.
                        //kemudian, selisih 'id' indikator yang diperoleh diasumsikan sebagai 'id' indikator baru.
                        $newForChild = [];
                        $i = 0;
                        foreach ($indicatorsFromInput as $value) {
                            if (!in_array($value, $indicatorsOldMasterAndChild)) {
                                $newForChild[$i] = $value;
                                $i++;
                            }
                        }

                        //mencari daftar 'code' dari 'id' indikator lama 'master' yang di un-checked.
                        //daftar 'code' cerminan 'id' indikator dari 'super-master'.
                        $codesOldUnchecked = [];
                        $i = 0;
                        foreach ($oldForMaster as $value) {
                            $codesOldUnchecked[$i] = $this->indicatorRepository->findCodeColumnById($value);
                            $i++;
                        }

                        //mencari daftar 'id indikator 'child' dari 'id' indikator lama 'master' yang di un-checked.
                        $indicatorsUncheckedAtChild = $this->indicatorRepository->findIdByCodeList($codesOldUnchecked, $levelId, $unit->id, $year);

                        //mencari selisih 'id' indikator dengan cara membandingkan 'id' dari indikator lama dengan 'id' dari indikator request.
                        //kemudian, selisih 'id' indikator yang diperoleh diasumsikan sebagai 'id' indikator lama.
                        $oldForChild = [];
                        $i = 0;
                        foreach ($indicatorsUncheckedAtChild as $indicatorUncheckedAtChild) {
                            $oldForChild[$i] = $indicatorUncheckedAtChild->id;
                            $i++;
                        }

                        if (count($newForChild) > 0) { //terdapat 'id' indikator baru.

                            //nasab 'id' indikator baru.
                            //nasab berupa daftar 'id' indikator yang mencerminkan jalur keturunan ke atas dari 'id' indikator baru.
                            $familiesOfIndicatorNewForChild = [];
                            foreach ($newForChild as $value) {
                                $familiesOfIndicatorNewForChild = array_merge($familiesOfIndicatorNewForChild, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
                            }

                            //daftar 'code' dari 'id' indikator lama sesuai 'level_id', 'unit_id' & 'year' yang masih di checked.
                            //daftar 'code' mencerminkan 'id' indikator pada 'super-master'.
                            $indicatorsOnlyCodeForChild = $this->indicatorRepository->findAllCodeByLevelIdAndUnitIdAndYear($levelId, $unit->id, $year);
                            $codesIndicatorOldForChild = [];
                            $i = 0;
                            foreach ($indicatorsOnlyCodeForChild as $indicatorOnlyCode) {
                                if (!in_array($indicatorOnlyCode->id, $oldForChild)) { //seleksi 'id' indikator lama yang masih di checked.
                                    $codesIndicatorOldForChild[$i] = $indicatorOnlyCode->code;
                                    $i++;
                                }
                            }

                            ///gabungan 'id' indikator lama yang masih di checked & 'id' indikator baru.
                            $familiesIndicatorForChild = array_unique(array_merge($familiesOfIndicatorNewForChild, $codesIndicatorOldForChild));

                            //build ID
                            $idListChild = [];
                            foreach ($familiesIndicatorForChild as $familyId) {
                                if (!is_null($familyId)) {
                                    $idListChild[$familyId] = (string) Str::orderedUuid();
                                }
                            }

                            $i = 0;
                            foreach ($familiesIndicatorForChild as $familyId) {
                                if (!is_null($familyId)) {

                                    $indicatorSuperMaster = $this->indicatorRepository->findById($familyId); //indikator dari 'super-master'

                                    if (in_array($familyId, $codesIndicatorOldForChild)) {
                                        $indicatorOld = $this->indicatorRepository->findAllByCodeAndLevelIdAndUnitIdAndYear($familyId, $levelId, $unit->id, $year); //indikator dari 'child'

                                        //salin indikator lama
                                        $indicatorDomains->id = $idListChild[$indicatorSuperMaster->id];
                                        $indicatorDomains->indicator = $indicatorOld->indicator;
                                        $indicatorDomains->formula = $indicatorOld->formula;
                                        $indicatorDomains->measure = $indicatorOld->measure;
                                        $indicatorDomains->weight = $indicatorOld->getRawOriginal('weight');
                                        $indicatorDomains->polarity = $indicatorOld->getRawOriginal('polarity');
                                        $indicatorDomains->year = $year;
                                        $indicatorDomains->reducing_factor = $indicatorOld->reducing_factor;
                                        $indicatorDomains->validity = $indicatorOld->getRawOriginal('validity');
                                        $indicatorDomains->reviewed = $indicatorOld->reviewed;
                                        $indicatorDomains->referenced = $indicatorOld->referenced;
                                        $indicatorDomains->dummy = $indicatorOld->dummy;
                                        $indicatorDomains->label = 'child';
                                        $indicatorDomains->unit_id = $unit->id;
                                        $indicatorDomains->level_id = $levelId;
                                        $indicatorDomains->order = $i+1;
                                        $indicatorDomains->code = $indicatorSuperMaster->code;
                                        $indicatorDomains->parent_vertical_id = $this->indicatorRepository->findIdByCodeAndLevelIdAndYear($indicatorSuperMaster->id, $levelId, $year);
                                        $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $idListChild[$indicatorSuperMaster->parent_horizontal_id];
                                        $indicatorDomains->created_by = $indicatorOld->created_by;

                                        $this->indicatorRepository->save($indicatorDomains); //membuat indikator baru

                                        if (!$indicatorOld->dummy) {
                                            //target & realisasi 'CHILD' creating
                                            if (!is_null($indicatorOld->validity)) {
                                                foreach ($indicatorOld->validity as $key => $value) {
                                                    $targetDomains->id = (string) Str::orderedUuid();
                                                    $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $targetDomains->month = $key;
                                                    $targetDomains->value = 0;
                                                    $targetDomains->locked = true;
                                                    $targetDomains->default = true;

                                                    $this->targetRepository->save($targetDomains); //membuat target baru

                                                    $realizationDomains->id = (string) Str::orderedUuid();
                                                    $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                    $realizationDomains->month = $key;
                                                    $realizationDomains->value = 0;
                                                    $realizationDomains->locked = true;
                                                    $realizationDomains->default = true;

                                                    $this->realizationRepository->save($realizationDomains); //membuat realisasi baru
                                                }
                                            }
                                        }

                                        foreach ($indicatorOld->targets as $target) {
                                            $this->targetRepository->deleteById($target->id); //hapus target lama
                                        }

                                        foreach ($indicatorOld->realizations as $realization) {
                                            $this->realizationRepository->deleteById($realization->id); //hapus realisasi lama
                                        }

                                        $this->indicatorRepository->deleteById($indicatorOld->id); //hapus indikator lama
                                    } else {
                                        //buat baru
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
                                        $indicatorDomains->order = $i+1;
                                        $indicatorDomains->code = $indicatorSuperMaster->code;
                                        $indicatorDomains->parent_vertical_id = $this->indicatorRepository->findIdByCodeAndLevelIdAndYear($indicatorSuperMaster->id, $levelId, $year);
                                        $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $idListChild[$indicatorSuperMaster->parent_horizontal_id];
                                        $indicatorDomains->created_by = $userId;

                                        $this->indicatorRepository->save($indicatorDomains);

                                        //target & realisasi 'CHILD' creating
                                        if (!is_null($indicatorSuperMaster->validity)) {
                                            foreach ($indicatorSuperMaster->validity as $key => $value) {
                                                $targetDomains->id = (string) Str::orderedUuid();
                                                $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $targetDomains->month = $key;
                                                $targetDomains->value = 0;
                                                $targetDomains->locked = true;
                                                $targetDomains->default = true;

                                                $this->targetRepository->save($targetDomains);

                                                $realizationDomains->id = (string) Str::orderedUuid();
                                                $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                                $realizationDomains->month = $key;
                                                $realizationDomains->value = 0;
                                                $realizationDomains->locked = true;
                                                $realizationDomains->default = true;

                                                $this->realizationRepository->save($realizationDomains);
                                            }
                                        }
                                    }
                                    $i++;
                                }
                            }
                        }

                        if (count($oldForChild) > 0) { //terdapat 'id' indikator lama yang di un-checked.
                            foreach ($oldForChild as $v) {
                                $this->targetRepository->deleteByIndicatorId($v); //delete target
                                $this->realizationRepository->deleteByIndicatorId($v); //delete realisasi
                                $this->indicatorRepository->deleteById($v); //delete indikator
                            }
                        }
                    }
                    //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
                }

                if (count($oldForMaster) > 0) { //terdapat 'id' indikator lama yang di un-checked.
                    foreach ($oldForMaster as $v) {
                        $this->targetRepository->deleteByIndicatorId($v); //delete target
                        $this->indicatorRepository->deleteById($v); //delete indikator
                    }
                }
            } else {
                $indicatorsOldOnlyIdForChild = Arr::flatten($this->indicatorRepository->findAllIdIsByLevelIdAndUnitIdAndYear($levelId, $unitId, $year));

                $newForMaster = [];
                $i = 0;
                foreach ($indicatorsFromInput as $value) {
                    if (!in_array($value, $indicatorsOldOnlyIdForChild)) {
                        $newForMaster[$i] = $value;
                        $i++;
                    }
                }

                $indicatorsOldOnlyCodeForMaster = Arr::flatten($this->indicatorRepository->findAllCodeIsByLevelIdAndUnitIdAndYear($levelId, null, $year));

                $newIndicatorIsExisInMaster = [];
                $newIndicatorNotExisInMaster = [];
                $i = 0;
                $j = 0;
                foreach ($newForMaster as $value) {
                    if (in_array($value, $indicatorsOldOnlyCodeForMaster)) { //'id' indikator sudah terdaftar di 'master'
                        $newIndicatorIsExisInMaster[$i] = $value;
                        $i++;
                    } else { //'id' indikator belum terdaftar di 'master'
                        $newIndicatorNotExisInMaster[$j] = $value;
                        $j++;
                    }
                }

                if (count($newIndicatorNotExisInMaster) > 0) { //terdapat 'id' indikator baru yang belum terdaftar di 'master'.

                    //nasab 'id' indikator baru yang belum terdaftar di 'master'.
                    //nasab berupa daftar 'id' indikator yang mencerminkan jalur keturunan ke atas dari 'id' indikator baru yang belum terdaftar di 'master'.
                    $pathsOfIndicatorNewForMaster = [];
                    foreach ($newIndicatorNotExisInMaster as $value) {
                        $pathsOfIndicatorNewForMaster = array_merge($pathsOfIndicatorNewForMaster, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
                    }

                    //gabungan 'id' indikator master & 'id' indikator baru yang belum terdaftar di 'master'.
                    $pathsIndicatorForMaster = array_unique(array_merge($pathsOfIndicatorNewForMaster, $indicatorsOldOnlyCodeForMaster));

                    //section: paper work 'MASTER' updating ----------------------------------------------------------------------

                    //build ID
                    $idListMaster = [];
                    foreach ($pathsIndicatorForMaster as $familyId) {
                        if (!is_null($familyId)) {
                            $idListMaster[$familyId] = (string) Str::orderedUuid();
                        }
                    }

                    $indicatorsSuspended = [];
                    $i = 0;
                    $j = 0;
                    foreach ($pathsIndicatorForMaster as $familyId) {
                        if (!is_null($familyId)) { //mencegah array gabungan 'id' indikator master & 'id' indikator baru yang belum terdaftar di 'master' item-nya ada null
                            if (!in_array($familyId, $indicatorsOldOnlyCodeForMaster)) { //indikator tidak ada pada daftar indikator master

                                $familiesOfIndicator = Arr::flatten($this->indicatorRepository->findAllWithParentsById($familyId));

                                $haveFamiliesIndicatorNotRegisted = [];
                                $k = 0;
                                foreach ($familiesOfIndicator as $familyOfIndicator) {
                                    if ($familyOfIndicator !== $familyId) {
                                        if (!in_array($familyOfIndicator, Arr::flatten($this->indicatorRepository->findAllCodeIsByLevelIdAndUnitIdAndYear($levelId, null, $year)))) {
                                            $haveFamiliesIndicatorNotRegisted[$k] = $familyOfIndicator;
                                            $k++;
                                        }
                                    }
                                }

                                $indicatorSuperMaster = $this->indicatorRepository->findById($familyId); //indikator dari 'super-master'

                                if (count($haveFamiliesIndicatorNotRegisted) === 0) {
                                    //buat baru
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
                                    $indicatorDomains->order = $i+1;
                                    $indicatorDomains->code = $indicatorSuperMaster->code;
                                    $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                    $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->findIdColumnByCodeMaster($indicatorSuperMaster->parent_horizontal_id, $levelId, $year);
                                    $indicatorDomains->created_by = $userId;

                                    $this->indicatorRepository->save($indicatorDomains);

                                    //target 'MASTER' creating
                                    if (!is_null($indicatorSuperMaster->validity)) {
                                        foreach ($indicatorSuperMaster->validity as $key => $value) {
                                            $targetDomains->id = (string) Str::orderedUuid();
                                            $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                            $targetDomains->month = $key;
                                            $targetDomains->value = 0;
                                            $targetDomains->locked = true;
                                            $targetDomains->default = true;

                                            $this->targetRepository->save($targetDomains);
                                        }
                                    }
                                } else {
                                    $indicatorsSuspended[$j]['id'] = $familyId;
                                    $indicatorsSuspended[$j]['order'] = $i+1;
                                    $j++;
                                }
                            }
                            $i++;
                        }
                    }

                    dump("indikator suspended");
                    dump($indicatorsSuspended);

                    while (count($indicatorsSuspended) !== 0) {
                        for ($i=0; $i < count($indicatorsSuspended); $i++) {

                            $familiesOfIndicator = Arr::flatten($this->indicatorRepository->findAllWithParentsById($indicatorsSuspended[$i]['id']));

                            $haveFamiliesIndicatorNotRegisted = [];
                            $k = 0;
                            foreach ($familiesOfIndicator as $familyOfIndicator) {
                                if ($familyOfIndicator !== $indicatorsSuspended[$i]['id']) {
                                    if (!in_array($familyOfIndicator, Arr::flatten($this->indicatorRepository->findAllCodeIsByLevelIdAndUnitIdAndYear($levelId, null, $year)))) {
                                        $haveFamiliesIndicatorNotRegisted[$k] = $familyOfIndicator;
                                        $k++;
                                    }
                                }
                            }

                            $indicatorSuperMaster = $this->indicatorRepository->findById($indicatorsSuspended[$i]['id']); //indikator dari 'super-master'

                            if (count($haveFamiliesIndicatorNotRegisted) === 0) {
                                //buat baru
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
                                $indicatorDomains->order = $indicatorsSuspended[$i]['order'];
                                $indicatorDomains->code = $indicatorSuperMaster->code;
                                $indicatorDomains->parent_vertical_id = $indicatorSuperMaster->id;
                                $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->findIdColumnByCodeMaster($indicatorSuperMaster->parent_horizontal_id, $levelId, $year);
                                $indicatorDomains->created_by = $userId;

                                $this->indicatorRepository->save($indicatorDomains);

                                //target 'MASTER' creating
                                if (!is_null($indicatorSuperMaster->validity)) {
                                    foreach ($indicatorSuperMaster->validity as $key => $value) {
                                        $targetDomains->id = (string) Str::orderedUuid();
                                        $targetDomains->indicator_id = $idListMaster[$indicatorSuperMaster->id];
                                        $targetDomains->month = $key;
                                        $targetDomains->value = 0;
                                        $targetDomains->locked = true;
                                        $targetDomains->default = true;

                                        $this->targetRepository->save($targetDomains);
                                    }
                                }
                                unset($indicatorsSuspended[$i]);
                            }
                        }
                    }
                    //end section: paper work 'MASTER' updating ----------------------------------------------------------------------

                    //section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    $indicatorsOldOnlyCodeForMaster = Arr::flatten($this->indicatorRepository->findAllCodeIsByLevelIdAndUnitIdAndYear($levelId, null, $year));

                    $newForChild = [];
                    $i = 0;
                    foreach ($indicatorsFromInput as $value) {
                        if (!in_array($value, $indicatorsOldOnlyIdForChild)) {
                            $newForChild[$i] = $value;
                            $i++;
                        }
                    }

                    $oldForChild = [];
                    $i = 0;
                    foreach ($indicatorsOldOnlyIdForChild as $value) {
                        if (!in_array($value, $indicatorsFromInput)) {
                            $oldForChild[$i] = $value;
                            $i++;
                        }
                    }

                    if (count($newForChild) > 0) { //terdapat 'id' indikator baru.

                        //nasab 'id' indikator baru.
                        //nasab berupa daftar 'id' indikator yang mencerminkan jalur keturunan ke atas dari 'id' indikator baru.
                        $familiesOfIndicatorNewForChild = [];
                        foreach ($newForChild as $value) {
                            $familiesOfIndicatorNewForChild = array_merge($familiesOfIndicatorNewForChild, Arr::flatten($this->indicatorRepository->findAllWithParentsById($value)));
                        }

                        //daftar 'code' dari 'id' indikator lama sesuai 'level_id', 'unit_id' & 'year' yang masih di checked.
                        //daftar 'code' mencerminkan 'id' indikator pada 'super-master'.
                        $indicatorsOnlyIdAndCodeForChild = $this->indicatorRepository->findAllCodeByLevelIdAndUnitIdAndYear($levelId, $unitId, $year);
                        $codesIndicatorOldCheckedForChild = [];
                        $i = 0;
                        foreach ($indicatorsOnlyIdAndCodeForChild as $indicatorOnlyIdAndCode) {
                            if (!in_array($indicatorOnlyIdAndCode->id, $oldForChild)) { //seleksi 'id' indikator lama yang masih di checked.
                                $codesIndicatorOldCheckedForChild[$i] = $indicatorOnlyIdAndCode->code;
                                $i++;
                            }
                        }

                        //gabungan 'id' indikator child & 'id' indikator baru yang belum terdaftar di 'child'.
                        $familiesIndicatorForChild = array_unique(array_merge($familiesOfIndicatorNewForChild, $codesIndicatorOldCheckedForChild));

                        //section: paper work 'CHILD' updating ----------------------------------------------------------------------

                        //build ID
                        $idListChild = [];
                        foreach ($familiesIndicatorForChild as $familyId) {
                            if (!is_null($familyId)) {
                                $idListChild[$familyId] = (string) Str::orderedUuid();
                            }
                        }

                        $i = 0;
                        foreach ($familiesIndicatorForChild as $familyId) {
                            if (!is_null($familyId)) { //mencegah array gabungan 'id' indikator child & 'id' indikator baru yang belum terdaftar di 'child' item-nya ada null

                                $indicatorSuperMaster = $this->indicatorRepository->findById($familyId); //indikator dari 'super-master'

                                if (!in_array($familyId, $codesIndicatorOldCheckedForChild)) { //indikator tidak ada pada daftar indikator child

                                    $indicator = $this->indicatorRepository->findByCodeAndLevelIdAndYear($indicatorSuperMaster->id, $levelId, $year);

                                    //buat baru
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
                                    $indicatorDomains->order = $i+1;
                                    $indicatorDomains->code = $indicatorSuperMaster->code;
                                    $indicatorDomains->parent_vertical_id = $indicator->id;
                                    $indicatorDomains->parent_horizontal_id = is_null($indicatorSuperMaster->parent_horizontal_id) ? null : $this->indicatorRepository->findIdColumnByCodeChild($indicator->code, $levelId, $unitId, $year);
                                    $indicatorDomains->created_by = $userId;

                                    $this->indicatorRepository->save($indicatorDomains);

                                    //target 'MASTER' creating
                                    if (!is_null($indicatorSuperMaster->validity)) {
                                        foreach ($indicatorSuperMaster->validity as $key => $value) {
                                            $targetDomains->id = (string) Str::orderedUuid();
                                            $targetDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                            $targetDomains->month = $key;
                                            $targetDomains->value = 0;
                                            $targetDomains->locked = true;
                                            $targetDomains->default = true;

                                            $this->targetRepository->save($targetDomains);

                                            $realizationDomains->id = (string) Str::orderedUuid();
                                            $realizationDomains->indicator_id = $idListChild[$indicatorSuperMaster->id];
                                            $realizationDomains->month = $key;
                                            $realizationDomains->value = 0;
                                            $realizationDomains->locked = true;
                                            $realizationDomains->default = true;

                                            $this->realizationRepository->save($realizationDomains);
                                        }
                                    }
                                }
                                $i++;
                            }
                        }
                        //end section: paper work 'CHILD' updating ----------------------------------------------------------------------
                    }

                    if (count($oldForChild) > 0) { //terdapat 'id' indikator lama yang di un-checked.
                        foreach ($oldForChild as $v) {
                            $this->targetRepository->deleteByIndicatorId($v); //delete target
                            $this->realizationRepository->deleteByIndicatorId($v); //delete realisasi
                            $this->indicatorRepository->deleteById($v); //delete indikator
                        }
                    }
                    //end section: paper work 'MASTER' updating ----------------------------------------------------------------------
                }
            }
        });
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository, TargetRepository, RealizationRepository
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
