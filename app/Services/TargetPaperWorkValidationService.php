<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\UserRepository;
use App\Rules\LevelIsThisAndChildFromUser;
use App\Rules\LevelIsThisAndChildFromUser__Except__DataEntry_And_Employee;
use App\Rules\UnitIsThisAndChildUser;
use App\Rules\UnitIsThisAndChildUser__Except__DataEntry_And_Employee;
use App\Rules\UnitMatchWithLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class TargetPaperWorkValidationService {

    private ?UserRepository $userRepository;
    private ?IndicatorRepository $indicatorRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->userRepository = $constructRequest->userRepository;
            $this->indicatorRepository = $constructRequest->indicatorRepository;
        }
    }

    //use repo UserRepository
    public function editValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        //level yang dikirim sesuai dengan level si pengguna yang login atau level turunannya
        //unit yang dikirim sesuai dengan unit si pengguna yang login atau unit turunannya

        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsThisAndChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new UnitIsThisAndChildUser__Except__DataEntry_And_Employee($user)], //new UnitMatchWithLevel($request->query('level'))
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }

    //use repo UserRepository, IndicatorRepository
    public function updateValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        //pastikan KPI yang dikirim sesuai dengan di DB
        //pastikan KPI yang dikirim tidak ada status dummy
        //pastikan bulan yang dikirim sesuai dengan berlaku setiap KPI

        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsThisAndChildFromUser($user)],
            'unit' => ['required', 'string', new UnitIsThisAndChildUser($user), new UnitMatchWithLevel($request->query('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $levelId = $this->levelRepository->findIdBySlug($request->post('level'));
        $indicators = $request->post('unit') === 'master' ? Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, null, $request->post('tahun'))) : Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun')));

        //memastikan KPI yang dikirim sesuai dengan di DB
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $value) {
                if (!in_array($value, Arr::flatten($indicators))) {
                    $validator->errors()->add('indicators', "Akses ilegal !");
                    break;
                }
            }
        });

        $indicators = $request->post('unit') === 'master' ? Arr::flatten($this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($request->post('indicators'), $levelId, null, $request->post('tahun'))) : Arr::flatten($this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($request->post('indicators'), $levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun')));

        //memastikan KPI yang dikirim tidak ada status dummy
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($indicators as $indicator) {
                if ($indicator->dummy) {
                    $validator->errors()->add('indicators', "Akses ilegal !");
                    break;
                }
            }
        });

        return $validator;
    }
}
