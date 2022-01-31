<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\LevelIsThisAndChildFromUser__Except__DataEntry_And_Employee;
use App\Rules\UnitIsThisAndChildUser__Except__DataEntry_And_Employee;
use App\Rules\UnitMatchWithLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class TargetPaperWorkValidationService {

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

    //use repo UserRepository, IndicatorRepository, LevelRepository, UnitRepository
    public function updateValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'targets' => ['required'],
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsThisAndChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new UnitIsThisAndChildUser__Except__DataEntry_And_Employee($user), new UnitMatchWithLevel($request->post('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
        ];

        //memastikan target yang dikirim tipe data 'numeric'
        foreach ($request->post('targets') as $targetK => $targetV) {
            foreach ($targetV as $K => $V) {
                $attributes["targets.$targetK.$K"] = ['numeric'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $targets = $request->post('targets');
        $indicatorsId = array_keys($request->post('targets')); //list KPI dari target

        $levelId = $this->levelRepository->findIdBySlug($request->post('level'));
        $indicators = $request->post('unit') === 'master' ? Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, null, $request->post('tahun'))) : Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun')));

        //memastikan KPI yang dikirim terdaftar di DB
        $validator->after(function ($validator) use ($indicatorsId, $indicators) {
            foreach ($indicatorsId as $value) {
                if (!in_array($value, $indicators)) {
                    $validator->errors()->add('targets', "Akses ilegal !");
                    break;
                }
            }
        });

        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicatorsId, $levelId, null, $request->post('tahun')) : $this->indicatorRepository->findAllByLevelIdAndUnitIdAndYearAndIdList($indicatorsId, $levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang dikirim tidak ada status dummy
        $validator->after(function ($validator) use ($indicators) {
            foreach ($indicators as $indicator) {
                if ($indicator->dummy) {
                    $validator->errors()->add('targets', "Akses ilegal !");
                    break;
                }
            }
        });

        //pastikan bulan yang dikirim sesuai dengan masa berlaku setiap KPI
        $validator->after(function ($validator) use ($targets) {
            foreach ($targets as $targetK => $targetV) {
                $indicator = $this->indicatorRepository->findById($targetK);
                $validityMonths = array_keys($indicator->validity);

                $isError = false;
                foreach ($validityMonths as $validityMonth) {
                    if (!in_array($validityMonth, array_keys($targetV))) {
                        $validator->errors()->add('targets', "Akses ilegal !");
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