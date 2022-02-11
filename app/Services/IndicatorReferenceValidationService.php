<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Rules\IndicatorPaperWork__Available;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use App\Rules\Unit__MatchWith__Level;

class IndicatorReferenceValidationService
{
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
    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
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

        $indicators = $this->indicatorRepository->find__allId__by_SuperMasterLabel(); //get indicators paper work

        //memastikan semua KPI sesuai dengan kertas kerja KPI 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('indicators', "(#3.1) : Akses ilegal !");
                    break;
                }
            }
        });

        $indicators[count($indicators)] = ['id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        //memastikan semua preferensi KPI sesuai dengan kertas kerja KPI 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('preferences') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('preferences', "(#3.2) : Akses ilegal !");
                    break;
                }
            }
        });

        return $validator;
    }

    public function editValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan kertas kerja KPI yang akan dibuat sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $attributes = [
            'level' => ['required', 'string', new IndicatorPaperWork__Available($request->query('level'), $request->query('unit'), $request->query('tahun'))],
            'unit' => ['required_unless:level,super-master', 'string', 'in:master', new Unit__MatchWith__Level($request->query('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_unless' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function updateValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'indicators.*' => ['required', 'uuid'],
            'preferences.*' => ['required'],
            'level' => ['required', 'string'],
            'unit' => ['required_unless:level,super-master', 'string', 'in:master', new Unit__MatchWith__Level($request->post('level'))],
            'tahun' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_unless' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
            'date_format' => ':attribute harus berformat yyyy.',
            'in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $indicators = $request->post('level') === 'super-master' ? $this->indicatorRepository->find__id_parentHorizontalId__by__label_levelId_unitId_year('super-master', null, null, null) : $this->indicatorRepository->find__id_parentHorizontalId__by__label_levelId_unitId_year($request->post('unit') === 'master' ? 'master' : 'child', $this->levelRepository->find__id__by__slug($request->post('level')), $request->post('unit') === 'master' ? null : $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan semua KPI sesuai dengan kertas kerja KPI 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('indicators', "(#3.3) : Akses ilegal !");
                    break;
                }
            }
        });

        $indicators[count($indicators)] = ['id' => 'root', 'parent_horizontal_id' => 'root']; //sisipan, agar valid jika input-nya 'ROOT'

        //memastikan semua preferensi KPI sesuai dengan kertas kerja KPI 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('preferences') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('preferences', "(#3.4) : Akses ilegal !");
                    break;
                }
            }
        });

        return $validator;
    }
}
