<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UserRepository;
use App\Rules\LevelIsThisAndChildFromUser;
use App\Rules\LevelIsThisAndChildFromUser__Except__Employee;
use App\Rules\UnitIsThisAndChildUser;
use App\Rules\UnitIsThisAndChildUser__Except__Employee;
use App\Rules\UnitMatchWithLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class RealizationPaperWorkValidationService {

    private ?UserRepository $userRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
    }

    //use repo UserRepository
    public function editValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        //level yang dikirim sesuai dengan level si pengguna yang login atau level turunannya
        //unit yang dikirim sesuai dengan unit si pengguna yang login atau unit turunannya

        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsThisAndChildFromUser__Except__Employee($user)],
            'unit' => ['required', 'string', 'not_in:master', new UnitIsThisAndChildUser__Except__Employee($user)], //new UnitMatchWithLevel($request->query('level'))
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
    public function updateValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'realizations' => ['required'],
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsThisAndChildFromUser__Except__Employee($user)],
            'unit' => ['required', 'string', 'not_in:master', new UnitIsThisAndChildUser__Except__Employee($user), new UnitMatchWithLevel($request->post('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
        ];

        //memastikan realisasi yang dikirim tipe data 'numeric'
        foreach ($request->post('realizations') as $realizationK => $realizationV) {
            foreach ($realizationV as $K => $V) {
                $attributes["realizations.$realizationK.$K"] = ['numeric'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $realizations = $request->post('realizations');
        $indicatorsId = array_keys($request->post('realizations')); //list KPI dari realization

        $levelId = $this->levelRepository->findIdBySlug($request->post('level'));
        $indicators = $request->post('unit') === 'master' ? Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, null, $request->post('tahun'))) : Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun')));

        //memastikan KPI yang dikirim terdaftar di DB
        $validator->after(function ($validator) use ($indicatorsId, $indicators) {
            foreach ($indicatorsId as $value) {
                if (!in_array($value, $indicators)) {
                    $validator->errors()->add('realizations', "Akses ilegal !");
                    break;
                }
            }
        });

        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicatorsId, $levelId, null, $request->post('tahun')) : $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicatorsId, $levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang dikirim tidak ada status dummy
        $validator->after(function ($validator) use ($indicators) {
            foreach ($indicators as $indicator) {
                if ($indicator->dummy) {
                    $validator->errors()->add('realizations', "Akses ilegal !");
                    break;
                }
            }
        });

        //pastikan bulan yang dikirim sesuai dengan masa berlaku setiap KPI
        $validator->after(function ($validator) use ($realizations) {
            foreach ($realizations as $realizationK => $realizationV) {
                $indicator = $this->indicatorRepository->findById($realizationK);
                $validityMonths = array_keys($indicator->validity);

                $isError = false;
                foreach ($validityMonths as $validityMonth) {
                    if (!in_array($validityMonth, array_keys($realizationV))) {
                        $validator->errors()->add('realizations', "Akses ilegal !");
                        $isError = true;
                        break;
                    }
                }

                if ($isError) {
                    break;
                }
            }
        });

        return $validator;
    }
}
