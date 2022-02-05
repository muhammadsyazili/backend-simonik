<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorInsertOrUpdateRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicatorService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?TargetRepository $targetRepository;
    private ?RealizationRepository $realizationRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->indicatorRepository = $constructRequest->indicatorRepository;
        $this->levelRepository = $constructRequest->levelRepository;
        $this->targetRepository = $constructRequest->targetRepository;
        $this->realizationRepository = $constructRequest->realizationRepository;
    }

    //use repo IndicatorRepository, LevelRepository
    public function store(IndicatorInsertOrUpdateRequest $indicatorNew) : void
    {
        $indicator = new Indicator();

        DB::transaction(function () use ($indicatorNew, $indicator) {
            $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

            if ($indicatorNew->dummy === '1') {
                $indicator->dummy = true;
                $indicator->reducing_factor = null;
                $indicator->polarity = null;
                $indicator->validity = null;
                $indicator->weight = null;
            } else {
                if ($indicatorNew->reducing_factor === '1') {
                    $indicator->reducing_factor = true;
                    $indicator->polarity = null;
                } else {
                    $indicator->reducing_factor = false;
                    $indicator->polarity = $indicatorNew->polarity;
                }

                $indicator->dummy = false;
                $indicator->validity = $toJson['validity'];
                $indicator->weight =  $toJson['weight'];
            }

            $id = (string) Str::orderedUuid();

            $indicator->id = $id;
            $indicator->indicator = $indicatorNew->indicator;
            $indicator->formula = $indicatorNew->formula;
            $indicator->measure = $indicatorNew->measure;
            $indicator->year = null;
            $indicator->reviewed = true;
            $indicator->referenced = false;
            $indicator->label = 'super-master';
            $indicator->unit_id = null;
            $indicator->level_id = $this->levelRepository->find__id__by__slug('super-master');
            $indicator->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year('super-master');
            $indicator->code = null;
            $indicator->parent_vertical_id = null;
            $indicator->parent_horizontal_id = null;
            $indicator->created_by = $indicatorNew->user_id;

            $this->indicatorRepository->save($indicator);
            $this->indicatorRepository->update__code__by__id($id);
        });
    }

    //use repo IndicatorRepository
    public function edit(string|int $id)
    {
        $indicator = $this->indicatorRepository->find__with__level__by__id($id);
        $indicator->original_polarity = $indicator->getRawOriginal('polarity');
        return $indicator;
    }

    //use repo IndicatorRepository, TargetRepository, RealizationRepository
    public function update(IndicatorInsertOrUpdateRequest $indicatorNew, string|int $id) : void
    {
        $indicator = new Indicator();
        $target = new Target();
        $realization = new Realization();

        DB::transaction(function () use ($indicatorNew, $id, $indicator, $target, $realization) {

            $indicatorOld = $this->indicatorRepository->find__by__id($id);

            if ($indicatorOld->label === 'super-master') {
                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicator->dummy = true;
                    $indicator->reducing_factor = null;
                    $indicator->polarity = null;
                    $indicator->validity = null;
                    $indicator->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicator->reducing_factor = true;
                        $indicator->polarity = null;
                    } else {
                        $indicator->reducing_factor = false;
                        $indicator->polarity = $indicatorNew->polarity;
                    }

                    $indicator->dummy = false;
                    $indicator->validity = $toJson['validity'];
                    $indicator->weight =  $toJson['weight'];
                }

                $indicator->indicator = $indicatorNew->indicator;
                $indicator->formula = $indicatorNew->formula;
                $indicator->measure = $indicatorNew->measure;
                $indicator->year = $indicatorOld->year;
                $indicator->reviewed = $indicatorOld->reviewed;
                $indicator->referenced = $indicatorOld->referenced;
                $indicator->label = $indicatorOld->label;
                $indicator->unit_id = $indicatorOld->unit_id;
                $indicator->level_id = $indicatorOld->level_id;
                $indicator->order = $indicatorOld->order;
                $indicator->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicator->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                $this->indicatorRepository->update__by__id($indicator, $id); //update KPI
            } else if ($indicatorOld->label === 'master') {
                /**
                 * section: master
                 */

                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicator->dummy = true;
                    $indicator->reducing_factor = null;
                    $indicator->polarity = null;
                    $indicator->validity = null;
                    $indicator->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicator->reducing_factor = true;
                        $indicator->polarity = null;
                    } else {
                        $indicator->reducing_factor = false;
                        $indicator->polarity = $indicatorNew->polarity;
                    }

                    $indicator->dummy = false;
                    $indicator->validity = $toJson['validity'];
                    $indicator->weight =  $toJson['weight'];
                }

                $indicator->indicator = $indicatorNew->indicator;
                $indicator->formula = $indicatorNew->formula;
                $indicator->measure = $indicatorNew->measure;
                $indicator->year = $indicatorOld->year;
                $indicator->reviewed = $indicatorOld->reviewed;
                $indicator->referenced = $indicatorOld->referenced;
                $indicator->label = $indicatorOld->label;
                $indicator->unit_id = $indicatorOld->unit_id;
                $indicator->level_id = $indicatorOld->level_id;
                $indicator->order = $indicatorOld->order;
                $indicator->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicator->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                if (count($indicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                    $monthsOld = array_keys($indicatorOld->validity);
                    $monthsNew = array_keys($indicatorNew->validity);

                    //new VS old months
                    $new = [];
                    $i = 0;
                    foreach ($monthsNew as $monthNew) {
                        if (in_array($monthNew, $monthsOld) === false) {
                            $new[$i] = $monthNew;
                            $i++;
                        }
                    }

                    if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                        foreach ($new as $v) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $v;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $v;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }

                    //old VS new months
                    $old = [];
                    $i = 0;
                    foreach ($monthsOld as $monthOld) {
                        if (in_array($monthOld, $monthsNew) === false) {
                            $old[$i] = $monthOld;
                            $i++;
                        }
                    }

                    if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                        foreach ($old as $v) {
                            $this->targetRepository->delete__by__month_indicatorId($v, $id); //delete target
                            $this->realizationRepository->delete__by__month_indicatorId($v, $id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    if (count($indicatorNew->validity) > 0) {
                        foreach ($indicatorNew->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }
                }

                $this->indicatorRepository->update__by__id($indicator, $id); //update KPI

                /**
                 * section: childs
                 */

                //semua turunan KPI yang dipilih
                $familiesIndicatorOld = $this->indicatorRepository->findAllByParentVerticalId($id);

                if (count($familiesIndicatorOld) > 0) {
                    foreach ($familiesIndicatorOld as $familyIndicatorOld) {
                        //convert (validity & weight) from array to JSON string
                        $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                        if ($indicatorNew->dummy === '1') {
                            $indicator->dummy = true;
                            $indicator->reducing_factor = null;
                            $indicator->polarity = null;
                            $indicator->validity = null;
                            $indicator->weight = null;
                        } else {
                            if ($indicatorNew->reducing_factor === '1') {
                                $indicator->reducing_factor = true;
                                $indicator->polarity = null;
                            } else {
                                $indicator->reducing_factor = false;
                                $indicator->polarity = $indicatorNew->polarity;
                            }

                            $indicator->dummy = false;
                            $indicator->validity = $toJson['validity'];
                            $indicator->weight =  $toJson['weight'];
                        }

                        $indicator->indicator = $indicatorNew->indicator;
                        $indicator->formula = $indicatorNew->formula;
                        $indicator->measure = $indicatorNew->measure;
                        $indicator->year = $familyIndicatorOld->year;
                        $indicator->reviewed = $familyIndicatorOld->reviewed;
                        $indicator->referenced = $familyIndicatorOld->referenced;
                        $indicator->label = $familyIndicatorOld->label;
                        $indicator->unit_id = $familyIndicatorOld->unit_id;
                        $indicator->level_id = $familyIndicatorOld->level_id;
                        $indicator->order = $familyIndicatorOld->order;
                        $indicator->parent_vertical_id = $familyIndicatorOld->parent_vertical_id;
                        $indicator->parent_horizontal_id = $familyIndicatorOld->parent_horizontal_id;

                        if (count($familyIndicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                            $monthsOld = array_keys($familyIndicatorOld->validity);
                            $monthsNew = array_keys($indicatorNew->validity);

                            //new VS old months
                            $new = [];
                            $i = 0;
                            foreach ($monthsNew as $monthNew) {
                                if (in_array($monthNew, $monthsOld) === false) {
                                    $new[$i] = $monthNew;
                                    $i++;
                                }
                            }

                            if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                                foreach ($new as $v) {
                                    $target->id = (string) Str::orderedUuid();
                                    $target->indicator_id = $familyIndicatorOld->id;
                                    $target->month = $v;
                                    $target->value = 0;
                                    $target->locked = true;
                                    $target->default = true;

                                    $this->targetRepository->save($target); //save target

                                    $realization->id = (string) Str::orderedUuid();
                                    $realization->indicator_id = $familyIndicatorOld->id;
                                    $realization->month = $v;
                                    $realization->value = 0;
                                    $realization->locked = true;
                                    $realization->default = true;

                                    $this->realizationRepository->save($realization); //save realisasi
                                }
                            }

                            //old VS new months
                            $old = [];
                            $i = 0;
                            foreach ($monthsOld as $monthOld) {
                                if (in_array($monthOld, $monthsNew) === false) {
                                    $old[$i] = $monthOld;
                                    $i++;
                                }
                            }

                            if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                                foreach ($old as $v) {
                                    $this->targetRepository->delete__by__month_indicatorId($v, $familyIndicatorOld->id); //delete target
                                    $this->realizationRepository->delete__by__month_indicatorId($v, $familyIndicatorOld->id); //delete realisasi
                                }
                            }
                        } else { //masa berlaku lama nol
                            if (count($indicatorNew->validity) > 0) {
                                foreach ($indicatorNew->validity as $key => $value) {
                                    $target->id = (string) Str::orderedUuid();
                                    $target->indicator_id = $familyIndicatorOld->id;
                                    $target->month = $key;
                                    $target->value = 0;
                                    $target->locked = true;
                                    $target->default = true;

                                    $this->targetRepository->save($target); //save target

                                    $realization->id = (string) Str::orderedUuid();
                                    $realization->indicator_id = $familyIndicatorOld->id;
                                    $realization->month = $key;
                                    $realization->value = 0;
                                    $realization->locked = true;
                                    $realization->default = true;

                                    $this->realizationRepository->save($realization); //save realisasi
                                }
                            }
                        }

                        $this->indicatorRepository->update__by__id($indicator, $familyIndicatorOld->id); //update KPI
                    }
                }
            } else if ($indicatorOld->label === 'child') {
                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicator->dummy = true;
                    $indicator->reducing_factor = null;
                    $indicator->polarity = null;
                    $indicator->validity = null;
                    $indicator->weight = null;
                } else {
                    if ($indicatorNew->reducing_factor === '1') {
                        $indicator->reducing_factor = true;
                        $indicator->polarity = null;
                    } else {
                        $indicator->reducing_factor = false;
                        $indicator->polarity = $indicatorNew->polarity;
                    }

                    $indicator->dummy = false;
                    $indicator->validity = $toJson['validity'];
                    $indicator->weight =  $toJson['weight'];
                }

                $indicator->indicator = $indicatorNew->indicator;
                $indicator->formula = $indicatorNew->formula;
                $indicator->measure = $indicatorNew->measure;
                $indicator->year = $indicatorOld->year;
                $indicator->reviewed = $indicatorOld->reviewed;
                $indicator->referenced = $indicatorOld->referenced;
                $indicator->label = $indicatorOld->label;
                $indicator->unit_id = $indicatorOld->unit_id;
                $indicator->level_id = $indicatorOld->level_id;
                $indicator->order = $indicatorOld->order;
                $indicator->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicator->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                if (count($indicatorOld->validity) > 0) { //masa berlaku lama tidak nol
                    $monthsOld = array_keys($indicatorOld->validity);
                    $monthsNew = array_keys($indicatorNew->validity);

                    //new VS old months
                    $new = [];
                    $i = 0;
                    foreach ($monthsNew as $monthNew) {
                        if (in_array($monthNew, $monthsOld) === false) {
                            $new[$i] = $monthNew;
                            $i++;
                        }
                    }

                    if (count($new) > 0) { //terdapat selisih antara masa berlaku baru dengan lama
                        foreach ($new as $v) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $v;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $v;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }

                    //old VS new months
                    $old = [];
                    $i = 0;
                    foreach ($monthsOld as $monthOld) {
                        if (in_array($monthOld, $monthsNew) === false) {
                            $old[$i] = $monthOld;
                            $i++;
                        }
                    }

                    if (count($old) > 0) { //terdapat selisih antara masa berlaku lama dengan baru
                        foreach ($old as $v) {
                            $this->targetRepository->delete__by__month_indicatorId($v, $id); //delete target
                            $this->realizationRepository->delete__by__month_indicatorId($v, $id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    if (count($indicatorNew->validity) > 0) {
                        foreach ($indicatorNew->validity as $key => $value) {
                            $target->id = (string) Str::orderedUuid();
                            $target->indicator_id = $id;
                            $target->month = $key;
                            $target->value = 0;
                            $target->locked = true;
                            $target->default = true;

                            $this->targetRepository->save($target); //save target

                            $realization->id = (string) Str::orderedUuid();
                            $realization->indicator_id = $id;
                            $realization->month = $key;
                            $realization->value = 0;
                            $realization->locked = true;
                            $realization->default = true;

                            $this->realizationRepository->save($realization); //save realisasi
                        }
                    }
                }

                $this->indicatorRepository->update__by__id($indicator, $id); //update KPI
            }
        });
    }

    //use repo IndicatorRepository
    public function destroy(string|int $id) : void
    {
        DB::transaction(function () use ($id) {
            $this->indicatorRepository->delete__by__id($id);
        });
    }

    private function validity_and_weight__to__JSON(?array $validity, ?array $weight) : array
    {
        $jsonString = [];
        if (is_null($validity)) {
            $jsonString['validity'] = null;
            $jsonString['weight'] = null;
        } else {
            $WEIGHT = [];
            $VALIDITY = [];
            foreach ($validity as $key => $value) {
                $WEIGHT[$key] = is_null($weight) || !array_key_exists($key, $weight) ? (float) 0 : (float) $weight[$key];

                $VALIDITY[$key] = true;
            }
            $jsonString['validity'] = collect($VALIDITY)->toJson();
            $jsonString['weight'] = collect($WEIGHT)->toJson();
        }

        return ['validity' => $jsonString['validity'], 'weight' => $jsonString['weight']];
    }
}
