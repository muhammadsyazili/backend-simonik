<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\HaveIndicatorsNotMatchWithSuperMater;
use App\Rules\AllTargetAndAllRealizationIsDefault;
use App\Rules\LevelIsChildFromUser;
use App\Rules\LevelIsThisAndChildFromUser;
use App\Rules\IndicatorPaperWorkNotAvailable;
use App\Rules\IndicatorPaperWorkAvailable;
use App\Rules\UnitMatchWithLevel;
use App\Rules\UnitIsThisAndChildUser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class IndicatorPaperWorkValidationService {
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
    public function indexValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        //level yang dikirim sesuai dengan level si pengguna yang login atau level turunannya
        //unit yang dikirim sesuai dengan unit si pengguna yang login atau unit turunannya

        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new LevelIsThisAndChildFromUser($user)],
            'unit' => ['required_unless:level,super-master', 'string', new UnitIsThisAndChildUser($user)], //new UnitMatchWithLevel($request->query('level'))
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

    //use repo UserRepository
    public function storeValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        //level yang dikirim sesuai dengan level si pengguna yang login atau level turunannya
        //memastikan semua KPI yang dikirim mrupakan KPI yang bersumber dari super-master
        //memastikan kertas kerja KPI yang akan dibuat belum tersedia di DB

        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'indicators' => ['required', new HaveIndicatorsNotMatchWithSuperMater($request->post('indicators'))],
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsChildFromUser($user), new IndicatorPaperWorkNotAvailable($request->post('level'), $request->post('year'))],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }

    public function editValidation(string $level, string $unit, string $year) : \Illuminate\Contracts\Validation\Validator
    {
        //memastikan kertas kerja KPI yang akan dibuat sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new IndicatorPaperWorkAvailable($level, $unit, $year)],
            'unit' => ['required', 'string', new UnitMatchWithLevel($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        return Validator::make($input, $attributes, $messages);
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function updateValidation(Request $request, string $level, string $unit, string $year) : \Illuminate\Contracts\Validation\Validator
    {
        //memastikan kertas kerja KPI yang akan dibuat sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new IndicatorPaperWorkAvailable($level, $unit, $year)],
            'unit' => ['required', 'string', new UnitMatchWithLevel($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        $validator = Validator::make($input, $attributes, $messages);

        $levelId = $this->levelRepository->findIdBySlug($level);
        $indicators = $unit === 'master' ? Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, null, $year)) : Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($unit), $year));

        $new = [];
        $i = 0;
        foreach ($request->post('indicators') as $value) {
            if (!in_array($value, $indicators)) {
                $new[$i] = $value;
                $i++;
            }
        }

        $res = $this->indicatorRepository->countAllByIdListAndSuperMasterLabel($new);

        //memastikan jumlah KPI sama dengan di database
        $validator->after(function ($validator) use ($new, $res) {
            if (count($new) !== $res) {
                $validator->errors()->add('indicators', "Akses ilegal !");
            }
        });

        return $validator;
    }

    public function destroyValidation(string $level, string $unit, string $year) : \Illuminate\Contracts\Validation\Validator
    {
        //memastikan semua value target & realisasi masih default
        //memastikan kertas kerja KPI yang akan dibuat sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new AllTargetAndAllRealizationIsDefault($level, $unit, $year), new IndicatorPaperWorkAvailable($level, $unit, $year)],
            'unit' => ['required', 'string', new UnitMatchWithLevel($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        return Validator::make($input, $attributes, $messages);
    }

    //use repo IndicatorRepository, LevelRepository, UnitRepository
    public function reorderValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'level' => ['required', 'string'],
            'unit' => ['required_unless:level,super-master', 'string'],
            'year' => ['required_unless:level,super-master', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $indicators = [];
        if ($request->post('level') === 'super-master') {
            $indicators = Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear());
        } else {
            $levelId = $this->levelRepository->findIdBySlug($request->post('level'));
            $year = $request->post('year');
            if ($request->post('unit') === 'master') {
                $indicators = Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, null, $year));
            } else {
                $indicators = Arr::flatten($this->indicatorRepository->findAllIdByLevelIdAndUnitIdAndYear($levelId, $this->unitRepository->findIdBySlug($request->post('unit')), $year));
            }
        }

        //memastikan jumlah KPI sama dengan di database
        $validator->after(function ($validator) use ($request, $indicators) {
            if (count($request->post('indicators')) !== count($indicators)) {
                $validator->errors()->add('indicators', "Akses ilegal !");
            }
        });

        //memastikan semua KPI terdaftar di database
        $validator->after(function ($validator) use ($request, $indicators) {
            foreach ($request->post('indicators') as $indicator) {
                if (!in_array($indicator, $indicators)) {
                    $validator->errors()->add('indicators', "Akses ilegal !");
                    break;
                }
            }
        });

        return $validator;
    }
}
