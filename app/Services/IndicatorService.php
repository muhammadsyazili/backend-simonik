<?php

namespace App\Services;

use App\Domains\Indicator;
use App\DTO\IndicatorConstructRequenst;
use App\DTO\IndicatorInsertRequenst;
use App\DTO\IndicatorInsertResponse;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use Illuminate\Support\Str;

class IndicatorService {
    private IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;

    public function __construct(IndicatorConstructRequenst $indicatorConstructRequenst)
    {
        $this->indicatorRepository = $indicatorConstructRequenst->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequenst->levelRepository;
    }

    public function insert(IndicatorInsertRequenst $request) : IndicatorInsertResponse
    {
        $indicator = new Indicator();

        $toJson = $this->validity_and_weight_ToJson($request->validity, $request->weight);

        if ($request->dummy) { //indikator merupakan dummy
            $indicator->weight = null;
            $indicator->polarity = null;
            $indicator->reducing_factor = null;
            $indicator->validity = null;
            $indicator->dummy = true;
        } else {
            if ($request->reducing_factor) { //indikator merupakan faktor pengurang
                $indicator->polarity = null;
                $indicator->reducing_factor = true;
            } else { //indikator bukan merupakan faktor pengurang
                $indicator->polarity = $request->polarity;
                $indicator->reducing_factor = false;
            }

            $indicator->weight =  $toJson['weight'];
            $indicator->validity = $toJson['validity'];
            $indicator->dummy = false;
        }

        $id = (string) Str::orderedUuid();

        $indicator->id = $id;
        $indicator->indicator = $request->indicator;
        $indicator->formula = $request->formula;
        $indicator->measure = $request->measure;
        $indicator->year = null;
        $indicator->reviewed = true;
        $indicator->referenced = false;
        $indicator->label = 'super-master';
        $indicator->unit_id = null;
        $indicator->level_id = $this->levelRepository->findIdBySlug('super-master');
        $indicator->order = $this->indicatorRepository->countOrderColumn();
        $indicator->parent_vertical_id = null;
        $indicator->parent_horizontal_id = null;
        $indicator->created_by = $request->user_id;

        $insert = $this->indicatorRepository->save($indicator);

        if ($insert) {
            $this->indicatorRepository->updateCodeColumn($id);
        }

        $response = new IndicatorInsertResponse();
        $response->indicator = $indicator;

        return $response;
    }

    public function show($id)
    {
        $indicator = $this->indicatorRepository->findById($id);
        $indicator->original_polarity = $indicator->getRawOriginal('polarity');
        return $indicator;
    }

    private function validity_and_weight_ToJson($validity, $weight) : array
    {
        $jsonString = [];
        if (is_null($validity)) {
            $jsonString['validity'] = null;
            $jsonString['weight'] = null;
        } else {
            $WEIGHT = [];
            $VALIDITY = [];
            foreach ($validity as $key => $value) {
                $WEIGHT[Str::replace("'", null, $key)] = is_null($weight || !array_key_exists($key, $weight)) ?
                    (float) 0 :
                    (float) $weight[$key];

                $VALIDITY[Str::replace("'", null, $key)] = (int) $value;
            }
            $jsonString['validity'] = collect($VALIDITY)->toJson();
            $jsonString['weight'] = collect($WEIGHT)->toJson();
        }

        return ['validity' => $jsonString['validity'], 'weight' => $jsonString['weight']];
    }
}
