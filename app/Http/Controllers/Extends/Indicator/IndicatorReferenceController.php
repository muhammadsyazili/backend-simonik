<?php

namespace App\Http\Controllers\Extends\Indicator;

use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Rules\ValidRequestUnitBaseOnRequestLevel;
use App\Models\Indicator;
use App\Models\Unit;
use App\Models\Level;

class IndicatorReferenceController extends ApiController
{
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referencing showed",
            [
                'indicators' => Indicator::notReferenced()
                    ->where(['label' => 'super-master'])
                    ->get(),
                'preferences' =>Indicator::with('childsHorizontalRecursive')
                    ->rootHorizontal()
                    ->where(['label' => 'super-master'])
                    ->get(),
            ],
            null,
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $attributes = [
            'indicators.*' => ['required', 'uuid'],
            'preferences.*' => ['required'],
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes);

        $indicators = Indicator::where(['label' => 'super-master'])->get(['id'])->toArray(); //get indicators paper work

        //memastikan semua ID indikator dari request ada pada daftar ID indikator kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $key => $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add(
                        'indicators', "The 'indicator ID: $value' doesn't match with paper work 'level: super-master'."
                    );
                }
            }
        });

        $indicators[count($indicators)] = ['id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        //memastikan semua ID preferensi dari request ada pada daftar ID indikator kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('preferences') as $key => $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add(
                        'preferences', "The 'preference ID: $value' doesn't match with paper work 'level: super-master'."
                    );
                }
            }
        });

        if($validator->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        for ($i=0; $i < count($request->post('indicators')); $i++) {
            Indicator::where(['id' => $request->post('indicators')[$i]])
                ->update([
                    'parent_horizontal_id' => $request->post('preferences')[$i] === 'root' ? null : $request->post('preferences')[$i],
                    'referenced' => 1,
                ]);
        }

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referenced successfully",
            null,
            null,
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request)
    {
        $attributes = [
            'level' => ['required', 'string'],
            'unit' => ['required_unless:level,super-master', 'string', new ValidRequestUnitBaseOnRequestLevel($request->query('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        $validator = Validator::make($input, $attributes);

        if ($validator->fails()) {
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        // 'indicators' handler
        $indicators = Indicator::with('childsHorizontalRecursive')
        ->referenced()
        ->rootHorizontal()
        ->where(
            $request->query('level') === 'super-master' ?
            ['label' => 'super-master'] :
            [
                'level_id' => Level::firstWhere(['slug' => $request->query('level')])->id,
                'label' => $request->query('unit') === 'master' ? 'master' : 'child',
                'unit_id' => $request->query('unit') === 'master' ? null : Unit::firstWhere(['slug' => $request->query('unit')])->id,
                'year' => $request->query('tahun'),
            ]
        )
        ->get();

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referencing showed",
            [
                'indicators' => $indicators,
                'preferences' => $indicators,
            ],
            null,
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $attributes = [
            'indicators.*' => ['required', 'uuid'],
            'preferences.*' => ['required'],
            'level' => ['required', 'string'],
            'unit' => ['required_unless:level,super-master', 'string', new ValidRequestUnitBaseOnRequestLevel($request->post('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes);

        $year = $request->post('tahun');
        $levelId = Level::firstWhere(['slug' => $request->post('level')])->id;

        $indicators = Indicator::where(
            $request->post('level') === 'super-master' ?
                ['label' => 'super-master'] :
                [
                    'level_id' => $levelId,
                    'label' => $request->post('unit') === 'master' ? 'master' : 'child',
                    'unit_id' => $request->post('unit') === 'master' ? null : Unit::firstWhere(['slug' => $request->post('unit')])->id,
                    'year' => $request->post('tahun'),
                ]
        )
        ->get(['id', 'parent_horizontal_id'])
        ->toArray();

        //memastikan semua ID indikator dari request ada pada daftar ID indikator kertas kerja
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $key => $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add(
                        'indicators', "The 'indicator ID: $value' doesn't match with paper work 'level: super-master'."
                    );
                }
            }
        });

        $indicators[count($indicators)] = ['id' => 'root', 'parent_horizontal_id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        //memastikan semua ID preferensi dari request ada pada daftar ID indikator kertas kerja
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('preferences') as $key => $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add(
                        'preferences', "The 'preference ID: $value' doesn't match with paper work 'level: super-master'."
                    );
                }
            }
        });

        if($validator->fails()){
            return $this->APIResponse(
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY,
                Response::$statusTexts[Response::HTTP_UNPROCESSABLE_ENTITY],
                null,
                $validator->errors(),
            );
        }

        if ($request->post('level') !== 'super-master' && $request->post('unit') === 'master') {
            //section: paper work current request updating
            $log = [];
            for ($i=0; $i < count($request->post('indicators')); $i++) {

                $key = array_search($request->post('indicators')[$i], array_column($indicators, 'id'));

                $log[$i]['id'] = $request->post('indicators')[$i];
                $log[$i]['new_preference'] = $request->post('preferences')[$i] === 'root' ? null : $request->post('preferences')[$i];
                $log[$i]['old_preference'] = $indicators[$key]['id'];

                Indicator::where(['id' => $request->post('indicators')[$i]])
                    ->update([
                        'parent_horizontal_id' => $request->post('preferences')[$i] === 'root' ? null : $request->post('preferences')[$i],
                        'referenced' => 1,
                    ]);
            }
            //section end: paper work current request updating

            //section: paper work chlids updating
            foreach (Unit::with(['indicators' => function ($query) use ($year) {
                $query->where([
                    'year' => $year,
                ]);
            }])->where(['level_id' => $levelId])->get() as $unit) {

                foreach ($unit->indicators as $indicator) {
                    $key = array_search($indicator->parent_vertical_id, array_column($log, 'id'));

                    if ($key !== false) {
                        Indicator::where(['id' => $indicator->id])
                        ->update([
                            'parent_horizontal_id' => is_null($log[$key]['new_preference']) ? null : Indicator::firstWhere(
                                [
                                    'level_id' => $levelId,
                                    'unit_id' => $unit->id,
                                    'year' => $year,
                                    'parent_vertical_id' => $log[$key]['new_preference'],
                                ])->id,
                            'referenced' => 1,
                        ]);
                    }
                }

            }
            //end section: paper work chlids updating
        } else {
            for ($i=0; $i < count($request->post('indicators')); $i++) {
                Indicator::where(['id' => $request->post('indicators')[$i]])
                    ->update([
                        'parent_horizontal_id' => $request->post('preferences')[$i] === 'root' ? null : $request->post('preferences')[$i],
                        'referenced' => 1,
                    ]);
            }
        }

        return $this->APIResponse(
            true,
            Response::HTTP_OK,
            "Indicators referenced successfully",
            null,
            null,
        );
    }
}
