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
        //memastikan level yang dikirim sesuai dengan level si pengguna yang login atau level turunannya
        //memastikan unit yang dikirim sesuai dengan unit si pengguna yang login atau unit turunannya

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsChildFromUser__Except__DataEntry_And_Employee($user)],
            'unit' => ['required', 'string', new Unit__IsChildFromUser__Except__DataEntry_And_Employee($user)], //new Unit__MatchWith__Level($request->query('level'))
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
        //memastikan level yang dikirim sesuai dengan level si pengguna yang login atau level turunannya
        //memastikan unit yang dikirim sesuai dengan unit si pengguna yang login atau unit turunannya

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

        $levelId = $this->levelRepository->find__id__by__slug($request->post('level'));
        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang dikirim terdaftar di DB
        $validator->after(function ($validator) use ($indicatorsId, $indicators) {
            foreach ($indicatorsId as $value) {
                if (!in_array($value, $indicators)) {
                    $validator->errors()->add('targets', "(#5.1) : Akses ilegal !");
                    break;
                }
            }
        });

        $indicators = $request->post('unit') === 'master' ? $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, null, $request->post('tahun')) : $this->indicatorRepository->find__all__by__idList_levelId_unitId_year($indicatorsId, $levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $request->post('tahun'));

        //memastikan KPI yang dikirim tidak ada status dummy
        $validator->after(function ($validator) use ($indicators) {
            foreach ($indicators as $indicator) {
                if ($indicator->dummy) {
                    $validator->errors()->add('targets', "(#5.2) : Akses ilegal !");
                    break;
                }
            }
        });

        //pastikan bulan yang dikirim sesuai dengan masa berlaku setiap KPI
        $validator->after(function ($validator) use ($targets) {
            foreach ($targets as $targetK => $targetV) {
                $indicator = $this->indicatorRepository->find__by__id($targetK);
                $validityMonths = array_keys($indicator->validity);

                $isError = false;
                foreach ($validityMonths as $validityMonth) {
                    if (!in_array($validityMonth, array_keys($targetV))) {
                        $validator->errors()->add('targets', "(#5.3) : Akses ilegal !");
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
