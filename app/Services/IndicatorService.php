<?php

namespace App\Services;

use App\Domains\Indicator;
use App\Domains\Realization;
use App\Domains\Target;
use App\DTO\ConstructRequest;
use App\DTO\IndicatorDestroyRequest;
use App\DTO\IndicatorEditRequest;
use App\DTO\IndicatorEditResponse;
use App\DTO\IndicatorUpdateRequest;
use App\DTO\IndicatorStoreRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\RealizationRepository;
use App\Repositories\TargetRepository;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class IndicatorService
{
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
    public function store(IndicatorStoreRequest $indicator): void
    {
        DB::transaction(function () use ($indicator) {
            $indicatorDomain = new Indicator();

            $toJson = $this->validity_and_weight_and_weightCounted__to__JSON($indicator->validity, $indicator->weight);

            if ($indicator->dummy === '1') {
                $indicatorDomain->dummy = true;
                $indicatorDomain->reducing_factor = null;
                $indicatorDomain->polarity = null;
                $indicatorDomain->validity = null;
                $indicatorDomain->weight = null;
            } else {
                if ($indicator->reducingFactor === '1') {
                    $indicatorDomain->reducing_factor = true;
                    $indicatorDomain->polarity = null;
                } else {
                    $indicatorDomain->reducing_factor = false;
                    $indicatorDomain->polarity = $indicator->polarity;
                }

                $indicatorDomain->dummy = false;
                $indicatorDomain->validity = $toJson['validity'];
                $indicatorDomain->weight =  $toJson['weight'];
            }

            $indicatorDomain->id = (string) Str::orderedUuid();
            $indicatorDomain->indicator = $indicator->indicator;
            $indicatorDomain->type = $indicator->type;
            $indicatorDomain->formula = $indicator->formula;
            $indicatorDomain->measure = $indicator->measure;
            $indicatorDomain->year = null;
            $indicatorDomain->reviewed = true;
            $indicatorDomain->referenced = false;
            $indicatorDomain->label = 'super-master';
            $indicatorDomain->unit_id = null;
            $indicatorDomain->level_id = $this->levelRepository->find__id__by__slug('super-master');
            $indicatorDomain->order = $this->indicatorRepository->count__allPlusOne__by__levelId_unitId_year('super-master');
            $indicatorDomain->code = null;
            $indicatorDomain->parent_vertical_id = null;
            $indicatorDomain->parent_horizontal_id = null;
            $indicatorDomain->created_by = $indicator->userId;

            $this->indicatorRepository->save($indicatorDomain);
            $this->indicatorRepository->update__code__by__id($indicatorDomain->id);
        });
    }

    //use repo IndicatorRepository
    public function edit(IndicatorEditRequest $indicatorRequest): IndicatorEditResponse
    {
        $response = new IndicatorEditResponse();

        $indicator = $this->indicatorRepository->find__with__level__by__id($indicatorRequest->id);
        $indicator->original_polarity = $indicator->getRawOriginal('polarity');

        $indicator = [
            'id' => $indicator->id,
            'indicator' => $indicator->indicator,
            'type' => $indicator->type,
            'dummy' => $indicator->dummy,
            'reducing_factor' => $indicator->reducing_factor,
            'original_polarity' => $indicator->original_polarity,
            'formula' => $indicator->formula,
            'measure' => $indicator->measure,
            'label' => $indicator->label,
            'level_name' => $indicator->level->name,
            'validity' => [
                'jan' => ['checked' => array_key_exists('jan', (array) $indicator->validity) ? true : false],
                'feb' => ['checked' => array_key_exists('feb', (array) $indicator->validity) ? true : false],
                'mar' => ['checked' => array_key_exists('mar', (array) $indicator->validity) ? true : false],
                'apr' => ['checked' => array_key_exists('apr', (array) $indicator->validity) ? true : false],
                'may' => ['checked' => array_key_exists('may', (array) $indicator->validity) ? true : false],
                'jun' => ['checked' => array_key_exists('jun', (array) $indicator->validity) ? true : false],
                'jul' => ['checked' => array_key_exists('jul', (array) $indicator->validity) ? true : false],
                'aug' => ['checked' => array_key_exists('aug', (array) $indicator->validity) ? true : false],
                'sep' => ['checked' => array_key_exists('sep', (array) $indicator->validity) ? true : false],
                'oct' => ['checked' => array_key_exists('oct', (array) $indicator->validity) ? true : false],
                'nov' => ['checked' => array_key_exists('nov', (array) $indicator->validity) ? true : false],
                'dec' => ['checked' => array_key_exists('dec', (array) $indicator->validity) ? true : false],
            ],
            'weight' => [
                'jan' => ['value' => array_key_exists('jan', (array) $indicator->weight) ? $indicator->weight['jan'] : 0],
                'feb' => ['value' => array_key_exists('feb', (array) $indicator->weight) ? $indicator->weight['feb'] : 0],
                'mar' => ['value' => array_key_exists('mar', (array) $indicator->weight) ? $indicator->weight['mar'] : 0],
                'apr' => ['value' => array_key_exists('apr', (array) $indicator->weight) ? $indicator->weight['apr'] : 0],
                'may' => ['value' => array_key_exists('may', (array) $indicator->weight) ? $indicator->weight['may'] : 0],
                'jun' => ['value' => array_key_exists('jun', (array) $indicator->weight) ? $indicator->weight['jun'] : 0],
                'jul' => ['value' => array_key_exists('jul', (array) $indicator->weight) ? $indicator->weight['jul'] : 0],
                'aug' => ['value' => array_key_exists('aug', (array) $indicator->weight) ? $indicator->weight['aug'] : 0],
                'sep' => ['value' => array_key_exists('sep', (array) $indicator->weight) ? $indicator->weight['sep'] : 0],
                'oct' => ['value' => array_key_exists('oct', (array) $indicator->weight) ? $indicator->weight['oct'] : 0],
                'nov' => ['value' => array_key_exists('nov', (array) $indicator->weight) ? $indicator->weight['nov'] : 0],
                'dec' => ['value' => array_key_exists('dec', (array) $indicator->weight) ? $indicator->weight['dec'] : 0],
            ],
        ];

        $response->indicator = $indicator;

        return $response;
    }

    //use repo IndicatorRepository, TargetRepository, RealizationRepository
    public function update(IndicatorUpdateRequest $indicatorNew): void
    {
        DB::transaction(function () use ($indicatorNew) {
            $indicatorDomain = new Indicator();
            $targetDomain = new Target();
            $realizationDomain = new Realization();

            $indicatorOld = $this->indicatorRepository->find__by__id($indicatorNew->id);

            if ($indicatorOld->label === 'super-master') {
                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight_and_weightCounted__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicatorDomain->dummy = true;
                    $indicatorDomain->reducing_factor = null;
                    $indicatorDomain->polarity = null;
                    $indicatorDomain->validity = null;
                    $indicatorDomain->weight = null;
                } else {
                    if ($indicatorNew->reducingFactor === '1') {
                        $indicatorDomain->reducing_factor = true;
                        $indicatorDomain->polarity = null;
                    } else {
                        $indicatorDomain->reducing_factor = false;
                        $indicatorDomain->polarity = $indicatorNew->polarity;
                    }

                    $indicatorDomain->dummy = false;
                    $indicatorDomain->validity = $toJson['validity'];
                    $indicatorDomain->weight =  $toJson['weight'];
                }

                $indicatorDomain->id = $indicatorOld->id;
                $indicatorDomain->indicator = $indicatorNew->indicator;
                $indicatorDomain->type = $indicatorNew->type;
                $indicatorDomain->formula = $indicatorNew->formula;
                $indicatorDomain->measure = $indicatorNew->measure;
                $indicatorDomain->year = $indicatorOld->year;
                $indicatorDomain->reviewed = $indicatorOld->reviewed;
                $indicatorDomain->referenced = $indicatorOld->referenced;
                $indicatorDomain->label = $indicatorOld->label;
                $indicatorDomain->unit_id = $indicatorOld->unit_id;
                $indicatorDomain->level_id = $indicatorOld->level_id;
                $indicatorDomain->order = $indicatorOld->order;
                $indicatorDomain->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicatorDomain->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

                $this->indicatorRepository->update__by__id($indicatorDomain); //update indikator
            } else if ($indicatorOld->label === 'master') {
                /**
                 * section: master
                 */

                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight_and_weightCounted__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicatorDomain->dummy = true;
                    $indicatorDomain->reducing_factor = null;
                    $indicatorDomain->polarity = null;
                    $indicatorDomain->validity = null;
                    $indicatorDomain->weight = null;
                } else {
                    if ($indicatorNew->reducingFactor === '1') {
                        $indicatorDomain->reducing_factor = true;
                        $indicatorDomain->polarity = null;
                    } else {
                        $indicatorDomain->reducing_factor = false;
                        $indicatorDomain->polarity = $indicatorNew->polarity;
                    }

                    $indicatorDomain->dummy = false;
                    $indicatorDomain->validity = $toJson['validity'];
                    $indicatorDomain->weight =  $toJson['weight'];
                }

                $indicatorDomain->id = $indicatorOld->id;
                $indicatorDomain->indicator = $indicatorNew->indicator;
                $indicatorDomain->type = $indicatorNew->type;
                $indicatorDomain->formula = $indicatorNew->formula;
                $indicatorDomain->measure = $indicatorNew->measure;
                $indicatorDomain->year = $indicatorOld->year;
                $indicatorDomain->reviewed = $indicatorOld->reviewed;
                $indicatorDomain->referenced = $indicatorOld->referenced;
                $indicatorDomain->label = $indicatorOld->label;
                $indicatorDomain->unit_id = $indicatorOld->unit_id;
                $indicatorDomain->level_id = $indicatorOld->level_id;
                $indicatorDomain->order = $indicatorOld->order;
                $indicatorDomain->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicatorDomain->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

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
                            if ($indicatorNew->reducingFactor !== '1') { //bukan faktor reduksi
                                $targetDomain->id = (string) Str::orderedUuid();
                                $targetDomain->indicator_id = $indicatorOld->id;
                                $targetDomain->month = $v;
                                $targetDomain->value = 0;
                                $targetDomain->locked = true;
                                $targetDomain->default = true;

                                $this->targetRepository->save($targetDomain); //save target
                            }

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $indicatorOld->id;
                            $realizationDomain->month = $v;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain); //save realisasi
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
                            $this->targetRepository->delete__by__month_indicatorId($v, $indicatorOld->id); //delete target
                            $this->realizationRepository->delete__by__month_indicatorId($v, $indicatorOld->id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    if (!is_null($indicatorNew->validity)) {
                        if (count($indicatorNew->validity) > 0) {
                            foreach ($indicatorNew->validity as $key => $value) {
                                if ($indicatorNew->reducingFactor !== '1') { //bukan faktor reduksi
                                    $targetDomain->id = (string) Str::orderedUuid();
                                    $targetDomain->indicator_id = $indicatorOld->id;
                                    $targetDomain->month = $key;
                                    $targetDomain->value = 0;
                                    $targetDomain->locked = true;
                                    $targetDomain->default = true;

                                    $this->targetRepository->save($targetDomain); //save target
                                }

                                $realizationDomain->id = (string) Str::orderedUuid();
                                $realizationDomain->indicator_id = $indicatorOld->id;
                                $realizationDomain->month = $key;
                                $realizationDomain->value = 0;
                                $realizationDomain->locked = true;
                                $realizationDomain->default = true;

                                $this->realizationRepository->save($realizationDomain); //save realisasi
                            }
                        }
                    }
                }

                $this->indicatorRepository->update__by__id($indicatorDomain); //update indikator

                /**
                 * section: childs
                 */

                //semua turunan indikator yang dipilih
                $familiesIndicatorOld = $this->indicatorRepository->findAllByParentVerticalId($indicatorOld->id);

                if (count($familiesIndicatorOld) > 0) {
                    foreach ($familiesIndicatorOld as $familyIndicatorOld) {
                        //convert (validity & weight) from array to JSON string
                        $toJson = $this->validity_and_weight_and_weightCounted__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                        if ($indicatorNew->dummy === '1') {
                            $indicatorDomain->dummy = true;
                            $indicatorDomain->reducing_factor = null;
                            $indicatorDomain->polarity = null;
                            $indicatorDomain->validity = null;
                            $indicatorDomain->weight = null;
                        } else {
                            if ($indicatorNew->reducingFactor === '1') {
                                $indicatorDomain->reducing_factor = true;
                                $indicatorDomain->polarity = null;
                            } else {
                                $indicatorDomain->reducing_factor = false;
                                $indicatorDomain->polarity = $indicatorNew->polarity;
                            }

                            $indicatorDomain->dummy = false;
                            $indicatorDomain->validity = $toJson['validity'];
                            $indicatorDomain->weight =  $toJson['weight'];
                        }

                        $indicatorDomain->id = $familyIndicatorOld->id;
                        $indicatorDomain->indicator = $indicatorNew->indicator;
                        $indicatorDomain->type = $indicatorNew->type;
                        $indicatorDomain->formula = $indicatorNew->formula;
                        $indicatorDomain->measure = $indicatorNew->measure;
                        $indicatorDomain->year = $familyIndicatorOld->year;
                        $indicatorDomain->reviewed = $familyIndicatorOld->reviewed;
                        $indicatorDomain->referenced = $familyIndicatorOld->referenced;
                        $indicatorDomain->label = $familyIndicatorOld->label;
                        $indicatorDomain->unit_id = $familyIndicatorOld->unit_id;
                        $indicatorDomain->level_id = $familyIndicatorOld->level_id;
                        $indicatorDomain->order = $familyIndicatorOld->order;
                        $indicatorDomain->parent_vertical_id = $familyIndicatorOld->parent_vertical_id;
                        $indicatorDomain->parent_horizontal_id = $familyIndicatorOld->parent_horizontal_id;

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
                                    if ($indicatorNew->reducingFactor !== '1') { //bukan faktor reduksi
                                        $targetDomain->id = (string) Str::orderedUuid();
                                        $targetDomain->indicator_id = $familyIndicatorOld->id;
                                        $targetDomain->month = $v;
                                        $targetDomain->value = 0;
                                        $targetDomain->locked = true;
                                        $targetDomain->default = true;

                                        $this->targetRepository->save($targetDomain); //save target
                                    }

                                    $realizationDomain->id = (string) Str::orderedUuid();
                                    $realizationDomain->indicator_id = $familyIndicatorOld->id;
                                    $realizationDomain->month = $v;
                                    $realizationDomain->value = 0;
                                    $realizationDomain->locked = true;
                                    $realizationDomain->default = true;

                                    $this->realizationRepository->save($realizationDomain); //save realisasi
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
                            if (!is_null($indicatorNew->validity)) {
                                if (count($indicatorNew->validity) > 0) {
                                    foreach ($indicatorNew->validity as $key => $value) {
                                        if ($indicatorNew->reducingFactor !== '1') { //bukan faktor reduksi
                                            $targetDomain->id = (string) Str::orderedUuid();
                                            $targetDomain->indicator_id = $familyIndicatorOld->id;
                                            $targetDomain->month = $key;
                                            $targetDomain->value = 0;
                                            $targetDomain->locked = true;
                                            $targetDomain->default = true;

                                            $this->targetRepository->save($targetDomain); //save target
                                        }

                                        $realizationDomain->id = (string) Str::orderedUuid();
                                        $realizationDomain->indicator_id = $familyIndicatorOld->id;
                                        $realizationDomain->month = $key;
                                        $realizationDomain->value = 0;
                                        $realizationDomain->locked = true;
                                        $realizationDomain->default = true;

                                        $this->realizationRepository->save($realizationDomain); //save realisasi
                                    }
                                }
                            }
                        }

                        $this->indicatorRepository->update__by__id($indicatorDomain); //update indikator
                    }
                }
            } else if ($indicatorOld->label === 'child') {
                //convert (validity & weight) from array to JSON string
                $toJson = $this->validity_and_weight_and_weightCounted__to__JSON($indicatorNew->validity, $indicatorNew->weight);

                if ($indicatorNew->dummy === '1') {
                    $indicatorDomain->dummy = true;
                    $indicatorDomain->reducing_factor = null;
                    $indicatorDomain->polarity = null;
                    $indicatorDomain->validity = null;
                    $indicatorDomain->weight = null;
                } else {
                    if ($indicatorNew->reducingFactor === '1') {
                        $indicatorDomain->reducing_factor = true;
                        $indicatorDomain->polarity = null;
                    } else {
                        $indicatorDomain->reducing_factor = false;
                        $indicatorDomain->polarity = $indicatorNew->polarity;
                    }

                    $indicatorDomain->dummy = false;
                    $indicatorDomain->validity = $toJson['validity'];
                    $indicatorDomain->weight =  $toJson['weight'];
                }

                $indicatorDomain->id = $indicatorOld->id;
                $indicatorDomain->indicator = $indicatorNew->indicator;
                $indicatorDomain->type = $indicatorNew->type;
                $indicatorDomain->formula = $indicatorNew->formula;
                $indicatorDomain->measure = $indicatorNew->measure;
                $indicatorDomain->year = $indicatorOld->year;
                $indicatorDomain->reviewed = $indicatorOld->reviewed;
                $indicatorDomain->referenced = $indicatorOld->referenced;
                $indicatorDomain->label = $indicatorOld->label;
                $indicatorDomain->unit_id = $indicatorOld->unit_id;
                $indicatorDomain->level_id = $indicatorOld->level_id;
                $indicatorDomain->order = $indicatorOld->order;
                $indicatorDomain->parent_vertical_id = $indicatorOld->parent_vertical_id;
                $indicatorDomain->parent_horizontal_id = $indicatorOld->parent_horizontal_id;

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
                            if ($indicatorNew->reducingFactor !== '1') { //bukan faktor reduksi
                                $targetDomain->id = (string) Str::orderedUuid();
                                $targetDomain->indicator_id = $indicatorOld->id;
                                $targetDomain->month = $v;
                                $targetDomain->value = 0;
                                $targetDomain->locked = true;
                                $targetDomain->default = true;

                                $this->targetRepository->save($targetDomain); //save target
                            }

                            $realizationDomain->id = (string) Str::orderedUuid();
                            $realizationDomain->indicator_id = $indicatorOld->id;
                            $realizationDomain->month = $v;
                            $realizationDomain->value = 0;
                            $realizationDomain->locked = true;
                            $realizationDomain->default = true;

                            $this->realizationRepository->save($realizationDomain); //save realisasi
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
                            $this->targetRepository->delete__by__month_indicatorId($v, $indicatorOld->id); //delete target
                            $this->realizationRepository->delete__by__month_indicatorId($v, $indicatorOld->id); //delete realisasi
                        }
                    }
                } else { //masa berlaku lama nol
                    if (!is_null($indicatorNew->validity)) {
                        if (count($indicatorNew->validity) > 0) {
                            foreach ($indicatorNew->validity as $key => $value) {
                                if ($indicatorNew->reducingFactor !== '1') { //bukan faktor reduksi
                                    $targetDomain->id = (string) Str::orderedUuid();
                                    $targetDomain->indicator_id = $indicatorOld->id;
                                    $targetDomain->month = $key;
                                    $targetDomain->value = 0;
                                    $targetDomain->locked = true;
                                    $targetDomain->default = true;

                                    $this->targetRepository->save($targetDomain); //save target
                                }

                                $realizationDomain->id = (string) Str::orderedUuid();
                                $realizationDomain->indicator_id = $indicatorOld->id;
                                $realizationDomain->month = $key;
                                $realizationDomain->value = 0;
                                $realizationDomain->locked = true;
                                $realizationDomain->default = true;

                                $this->realizationRepository->save($realizationDomain); //save realisasi
                            }
                        }
                    }
                }

                $this->indicatorRepository->update__by__id($indicatorDomain); //update indikator
            }
        });
    }

    //use repo IndicatorRepository
    public function destroy(IndicatorDestroyRequest $indicatorRequest): void
    {
        DB::transaction(function () use ($indicatorRequest) {
            $this->indicatorRepository->delete__by__id($indicatorRequest->id);
        });
    }

    private function validity_and_weight_and_weightCounted__to__JSON(?array $validity, ?array $weight): array
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
