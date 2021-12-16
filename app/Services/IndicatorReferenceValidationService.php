<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Rules\UnitMatchOnRequestLevel;

class IndicatorReferenceValidationService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $indicatorConstructRequest)
    {
        $this->indicatorRepository = $indicatorConstructRequest->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequest->levelRepository;
        $this->unitRepository = $indicatorConstructRequest->unitRepository;
    }

    public function storeValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'indicators.*' => ['required', 'uuid'],
            'preferences.*' => ['required'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $indicators = $this->indicatorRepository->findAllIdBySuperMasterLabel(); //get indicators paper work

        //memastikan semua ID indikator dari request ada pada daftar ID indikator kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $key => $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add(
                        'indicators', "(ID indikator: $value) tidak cocok dengan kertas kerja (level: super master)."
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
                        'preferences', "(ID referensi: $value) tidak cocok dengan kertas kerja (level: super master)."
                    );
                }
            }
        });

        return $validator;
    }

    public function editValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'level' => ['required', 'string'],
            'unit' => ['required_unless:level,super-master', 'string', new UnitMatchOnRequestLevel($request->query('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_unless' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }

    public function updateValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'indicators.*' => ['required', 'uuid'],
            'preferences.*' => ['required'],
            'level' => ['required', 'string'],
            'unit' => ['required_unless:level,super-master', 'string', new UnitMatchOnRequestLevel($request->post('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_unless' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $indicators = $this->indicatorRepository->findIdAndParentHorizontalIdByWhere(
            $request->post('level') === 'super-master' ?
            ['label' => 'super-master'] :
            [
                'level_id' => $this->levelRepository->findIdBySlug($request->post('level')),
                'label' => $request->post('unit') === 'master' ? 'master' : 'child',
                'unit_id' => $request->post('unit') === 'master' ? null : $this->unitRepository->findIdBySlug($request->post('unit')),
                'year' => $request->post('tahun'),
            ]
        );

        //memastikan semua ID indikator dari request ada pada daftar ID indikator kertas kerja
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $key => $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add(
                        'indicators', "(ID indikator: $value) tidak cocok dengan kertas kerja (level: super master)."
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
                        'preferences', "(ID referensi: $value) tidak cocok dengan kertas kerja (level: super-master)."
                    );
                }
            }
        });

        return $validator;
    }
}
