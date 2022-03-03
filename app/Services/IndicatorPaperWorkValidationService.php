<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\Indicator__MatchWith__SuperMater_Indicator;
use App\Rules\AllTarget_And_AllRealization__IsDefault;
use App\Rules\Level__IsChildFromUser;
use App\Rules\Level__IsThisAndChildFromUser;
use App\Rules\IndicatorPaperWork__NotAvailable;
use App\Rules\IndicatorPaperWork__Available;
use App\Rules\Unit__MatchWith__Level;
use App\Rules\Unit__IsThisAndChildUser;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class IndicatorPaperWorkValidationService
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
    public function indexValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-read sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-read sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new Level__IsThisAndChildFromUser($user)],
            'unit' => ['required_unless:level,super-master', 'string', new Unit__IsThisAndChildUser($user)],
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
    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-store sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan semua KPI yang akan di-store merupakan KPI yang ber-label super-master
        //memastikan kertas kerja KPI yang akan di-store belum tersedia di DB

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'indicators' => ['required', new Indicator__MatchWith__SuperMater_Indicator($request->post('indicators'))],
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsChildFromUser($user), new IndicatorPaperWork__NotAvailable($request->post('level'), $request->post('year'))],
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

    public function editValidation(string $level, string $unit, string $year): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan kertas kerja KPI yang akan di-edit sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new IndicatorPaperWork__Available($level, $unit, $year)],
            'unit' => ['required', 'string', new Unit__MatchWith__Level($level)],
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
    public function updateValidation(Request $request, string $level, string $unit, string $year): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan kertas kerja KPI yang akan di-update sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new IndicatorPaperWork__Available($level, $unit, $year)],
            'unit' => ['required', 'string', new Unit__MatchWith__Level($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        $validator = Validator::make($input, $attributes, $messages);

        $levelId = $this->levelRepository->find__id__by__slug($level);
        $indicators = $unit === 'master' ? $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $year) : $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($unit), $year);

        $new = [];
        $i = 0;
        foreach ($request->post('indicators') as $value) {
            if (!in_array($value, $indicators)) {
                $new[$i] = $value;
                $i++;
            }
        }

        $res = $this->indicatorRepository->count__all__by__idList_superMasterLabel($new);

        //memastikan jumlah KPI yang akan di-update sama dengan di DB
        if (count($new) !== $res) {
            $validator->after(function ($validator) {
                $validator->errors()->add('indicators', "(#2.1) : Akses Ilegal !");
            });
        }

        return $validator;
    }

    //use repo UserRepository
    public function destroyValidation(string|int $userId, string $level, string $unit, string $year): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan semua target & realisasi masih default
        //memastikan kertas kerja KPI yang akan di-destroy sudah tersedia di DB
        //memastikan unit yang dikirim besesuaian dengan level

        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new AllTarget_And_AllRealization__IsDefault($user, $level, $unit, $year), new IndicatorPaperWork__Available($level, $unit, $year)],
            'unit' => ['required', 'string', new Unit__MatchWith__Level($level)],
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
    public function reorderValidation(Request $request): \Illuminate\Contracts\Validation\Validator
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
            $indicators = $this->indicatorRepository->find__allId__by__levelId_unitId_year();
        } else {
            $levelId = $this->levelRepository->find__id__by__slug($request->post('level'));
            $year = $request->post('year');
            if ($request->post('unit') === 'master') {
                $indicators = $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, null, $year);
            } else {
                $indicators = $this->indicatorRepository->find__allId__by__levelId_unitId_year($levelId, $this->unitRepository->find__id__by__slug($request->post('unit')), $year);
            }
        }

        //memastikan jumlah KPI yang akan di-reorder sama dengan di DB
        if (count($request->post('indicators')) !== count($indicators)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('indicators', "(#2.2) : Akses Ilegal !");
            });
        }

        //memastikan semua KPI yang akan di-reorder terdaftar di DB
        foreach ($request->post('indicators') as $indicator) {
            if (!in_array($indicator, $indicators)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('indicators', "(#2.3) : Akses Ilegal !");
                });
                break;
            }
        }

        return $validator;
    }
}
