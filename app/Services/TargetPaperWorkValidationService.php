<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\Level__IsChildFromUser__Except__DataEntry_And_Employee;
use App\Rules\Unit__IsChildFromUser__Except__DataEntry_And_Employee;
use App\Rules\Unit__MatchWith__Level;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class TargetPaperWorkValidationService
{
    private ?UserRepository $userRepository;
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
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
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new Unit__IsChildFromUser__Except__DataEntry_And_Employee($user)],
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

    //use repo UserRepository
    public function exportValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-edit sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-edit sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new Unit__IsChildFromUser__Except__DataEntry_And_Employee($user)],
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
            'targets' => ['required'],
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new Unit__IsChildFromUser__Except__DataEntry_And_Employee($user), new Unit__MatchWith__Level($request->post('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
            'gte' => [
                'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
                'file'    => ':attribute harus lebih besar dari atau sama dengan :value kilobytes.',
                'string'  => ':attribute harus lebih besar dari atau sama dengan :value characters.',
                'array'   => ':attribute harus memiliki :value item atau lebih.',
            ],
        ];

        //memastikan target yang akan di-update tipe data 'numeric'
        foreach ($request->post('targets') as $targetK => $targetV) {
            foreach ($targetV as $K => $V) {
                $attributes["targets.$targetK.$K"] = ['numeric', 'gte:0'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $targets = $request->post('targets');
        $indicatorsId = array_keys($request->post('targets')); //list KPI dari target

        //memastikan target yang akan di-update merupakan bulan jan-dec
        foreach ($targets as $months) {
            $isError = false;
            foreach ($months as $monthK => $monthV) {
                if (!in_array($monthK, ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('targets', "(#5.4) : Akses Ilegal !");
                    });
                    $isError = true;
                    break;
                }
            }
            if ($isError) {
                break;
            }
        }

        $levelId = $this->levelRepository->find__id__by__slug($request->post('level'));
        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang akan di-update terdaftar di DB
        foreach ($indicatorsId as $value) {
            if (!in_array($value, $indicators)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('targets', "(#5.1) : Akses Ilegal !");
                });
                break;
            }
        }

        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang akan di-update tidak ada yang ber-status dummy
        foreach ($indicators as $indicator) {
            if ($indicator->dummy) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('targets', "(#5.2) : Akses Ilegal !");
                });
                break;
            }
        }

        //pastikan bulan yang akan di-update sesuai dengan masa berlaku setiap KPI
        foreach ($targets as $targetK => $targetV) {
            $indicator = $this->indicatorRepository->find__by__id($targetK);
            $validityMonths = array_keys($indicator->validity);

            $isError = false;
            foreach ($validityMonths as $validityMonth) {
                if (!in_array($validityMonth, array_keys($targetV))) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('targets', "(#5.3) : Akses Ilegal !");
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

    //use repo UserRepository, IndicatorRepository, LevelRepository, UnitRepository
    public function updateImportValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-update sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-update sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $targets = $request->post('targets');
        $indicatorsId = array_keys($request->post('targets')); //list KPI dari target
        $levelId = $this->levelRepository->find__id__by__slug($request->post('level'));

        $attributes = [
            'targets' => ['required'],
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new Unit__IsChildFromUser__Except__DataEntry_And_Employee($user), new Unit__MatchWith__Level($request->post('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
            'gte' => [
                'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
                'file'    => ':attribute harus lebih besar dari atau sama dengan :value kilobytes.',
                'string'  => ':attribute harus lebih besar dari atau sama dengan :value characters.',
                'array'   => ':attribute harus memiliki :value item atau lebih.',
            ],
        ];

        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan target yang akan di-update tipe data 'numeric'
        foreach ($indicators as $indicator) {
            if (!$indicator->dummy) {
                foreach ($request->post('targets')[$indicator->id] as $monthName => $monthValue) {
                    $id = $indicator->id;
                    $attributes["targets.$id.$monthName"] = ['numeric', 'gte:0'];
                }
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        //memastikan target yang akan di-update merupakan bulan jan-dec
        foreach ($targets as $id => $months) {
            foreach ($months as $monthName => $monthValue) {
                if (!in_array($monthName, ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])) {
                    $validator->after(function ($validator) use ($id, $monthName) {
                        $validator->errors()->add("targets.$id.$monthName", "KPI ID: $id Bulan: $monthName tidak sah !");
                    });
                }
            }
        }

        $indicatorsIdDB = $request->post('unit') === 'master' ? $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang akan di-update terdaftar di DB
        foreach ($indicatorsId as $id) {
            if (!in_array($id, $indicatorsIdDB)) {
                $validator->after(function ($validator) use ($id) {
                    $validator->errors()->add("targets.$id", "KPI ID: $id tidak terdaftar di database !");
                });
            }
        }

        return $validator;
    }
}
