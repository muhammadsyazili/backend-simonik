<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Rules\PaperWorkNotAvailable;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Rules\UnitMatchOnRequestLevel;

class IndicatorReferenceValidationService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->indicatorRepository = $constructRequest->indicatorRepository;
            $this->levelRepository = $constructRequest->levelRepository;
            $this->unitRepository = $constructRequest->unitRepository;
        }
    }

    //use repo IndicatorRepository
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

        //memastikan semua ID KPI dari request ada pada daftar ID KPI kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('indicators', "Akses ilegal !");
                    break;
                }
            }
        });

        $indicators[count($indicators)] = ['id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        //memastikan semua ID preferensi dari request ada pada daftar ID KPI kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('preferences') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('preferences', "Akses ilegal !");
                    break;
                }
            }
        });

        return $validator;
    }

    public function editValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'level' => ['required', 'string', new PaperWorkNotAvailable($request->query('level'), $request->query('unit'), $request->query('tahun'))],
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

    //use repo IndicatorRepository, LevelRepository, UnitRepository
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

        $indicators = $request->post('level') === 'super-master' ? $this->indicatorRepository->findIdAndParentHorizontalIdByWhere('super-master', null, null, null) : $this->indicatorRepository->findIdAndParentHorizontalIdByWhere($request->post('unit') === 'master' ? 'master' : 'child', $this->levelRepository->findIdBySlug($request->post('level')), $request->post('unit') === 'master' ? null : $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun'));

        //memastikan semua ID KPI dari request ada pada daftar ID KPI kertas kerja
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('indicators', "Akses ilegal !");
                    break;
                }
            }
        });

        $indicators[count($indicators)] = ['id' => 'root', 'parent_horizontal_id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        //memastikan semua ID preferensi dari request ada pada daftar ID KPI kertas kerja
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('preferences') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('preferences', "Akses ilegal !");
                    break;
                }
            }
        });

        return $validator;
    }
}
