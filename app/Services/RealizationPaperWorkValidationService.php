<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\Level__IsThisAndChildFromUser__Except__Employee;
use App\Rules\Unit__IsThisAndChildUser__Except__Employee;
use App\Rules\Unit__MatchWith__Level;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class RealizationPaperWorkValidationService
{

    private ?UserRepository $userRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        if (!is_null($constructRequest)) {
            $this->userRepository = $constructRequest->userRepository;
            $this->indicatorRepository = $constructRequest->indicatorRepository;
            $this->levelRepository = $constructRequest->levelRepository;
            $this->unitRepository = $constructRequest->unitRepository;
        }
    }

    //use repo UserRepository
    public function editValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-edit sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-edit sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsThisAndChildFromUser__Except__Employee($user)],
            'unit' => ['required', 'string', 'not_in:master', new Unit__IsThisAndChildUser__Except__Employee($user)], //new Unit__MatchWith__Level($request->query('level'))
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

    //use repo UserRepository, IndicatorRepository, LevelRepository, UnitRepository
    public function updateValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-update sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-update sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'realizations' => ['required'],
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsThisAndChildFromUser__Except__Employee($user)],
            'unit' => ['required', 'string', 'not_in:master', new Unit__IsThisAndChildUser__Except__Employee($user), new Unit__MatchWith__Level($request->post('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
        ];

        //memastikan realisasi yang akan di-update tipe data 'numeric'
        foreach ($request->post('realizations') as $realizationK => $realizationV) {
            foreach ($realizationV as $K => $V) {
                $attributes["realizations.$realizationK.$K"] = ['numeric'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $realizations = $request->post('realizations');
        $indicatorsId = array_keys($request->post('realizations')); //list KPI dari realization

        $levelId = $this->levelRepository->find__id__by__slug($request->post('level'));
        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang akan di-update terdaftar di DB
        foreach ($indicatorsId as $value) {
            if (!in_array($value, $indicators)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('realizations', "(#4.1) : Akses ilegal !");
                });
                break;
            }
        }

        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang akan di-update tidak ada yang ber-status dummy
        foreach ($indicators as $indicator) {
            if ($indicator->dummy) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('realizations', "(#4.2) : Akses ilegal !");
                });
                break;
            }
        }

        //memastikan bulan yang akan di-update sesuai dengan masa berlaku setiap KPI
        foreach ($realizations as $realizationK => $realizationV) {
            $indicator = $this->indicatorRepository->find__by__id($realizationK);
            $validityMonths = array_keys($indicator->validity);

            $isError = false;
            foreach ($validityMonths as $validityMonth) {
                if (!in_array($validityMonth, array_keys($realizationV))) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('realizations', "(#4.3) : Akses ilegal !");
                    });
                    $isError = true;
                    break;
                }
            }

            if ($isError) {
                break;
            }
        }

        return $validator;
    }

    //use repo UserRepository, IndicatorRepository, UnitRepository
    public function changeLockValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'id' => ['required', 'string', 'uuid'], //new Level__IsThisAndChildFromUser__Except__DataEntry_And_Employee($user), new Unit__IsThisAndChildFromUser__Except__DataEntry_And_Employee($user)
            'month' => ['required', 'string', 'in:jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
            'in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = ['id' => $request->id, 'month' => $request->month];

        $validator = Validator::make($input, $attributes, $messages);

        $indicator = $this->indicatorRepository->find__by__id($request->id);

        //memastikan KPI yang akan di-update berlabel 'child'
        if (in_array($indicator->label, ['super-master', 'master'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('id', "(#4.4) : Akses ilegal !");
            });
        }

        //memastikan unit dari KPI yang akan di-update sesuai dengan unit user login saat ini atau unit turunan yang diizinkan
        if ($user->role->name !== 'super-admin') {
            if (!in_array($indicator->unit_id, $this->unitRepository->find__allFlattenId__with__this_childs__by__id($user->unit->id))) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('id', "Akses ilegal2 !");
                });
            }
        }

        return $validator;
    }
}
