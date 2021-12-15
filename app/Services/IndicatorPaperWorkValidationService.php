<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use App\Rules\ValidRequestLevelBaseOnUserRole;
use App\Rules\ValidRequestUnitBaseOnRequestLevel;
use App\Rules\ValidRequestUnitBaseOnUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class IndicatorPaperWorkValidationService {
    private ?IndicatorRepository $indicatorRepository;
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;
    private ?UserRepository $userRepository;

    public function __construct(ConstructRequest $indicatorConstructRequenst)
    {
        $this->indicatorRepository = $indicatorConstructRequenst->indicatorRepository;
        $this->levelRepository = $indicatorConstructRequenst->levelRepository;
        $this->unitRepository = $indicatorConstructRequenst->unitRepository;
        $this->userRepository = $indicatorConstructRequenst->userRepository;
    }

    public function indexValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->findWithRoleUnitLevelById(request()->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new ValidRequestLevelBaseOnUserRole($user)],
            'unit' => ['required_unless:level,super-master', 'string', new ValidRequestUnitBaseOnUserRole($user), new ValidRequestUnitBaseOnRequestLevel($request->query('level'))],
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

    public function storeValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->findWithRoleUnitLevelById(request()->header('X-User-Id'));

        $attributes = [
            'indicators' => ['required'],
            'level' => ['required', 'string', new ValidRequestLevelBaseOnUserRole($user)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $levelId = $this->levelRepository->findIdBySlug($request->post('level'));

        $sumOfIndicator = $this->indicatorRepository->countByWhere(['level_id' => $levelId, 'year' => $request->post('year')]);

        //memastikan paper work yang dibuat tidak duplikat pada level yang sama
        $validator->after(function ($validator) use ($sumOfIndicator, $request) {
            if ($sumOfIndicator > 0) {
                $validator->errors()->add(
                    'level', sprintf("Kertas kerja 'level: %s' 'year: %s' sudah tersedia.", $request->post('level'), $request->post('year'))
                );
            }
        });

        $indicatorsIdOfSuperMasterLevel = Arr::flatten($this->indicatorRepository->findAllIdReferencedBySuperMasterLabel());

        //memastikan semua ID indikator dari request ada pada daftar ID indikator kertas kerja 'SUPER MASTER'
        $validator->after(function ($validator) use ($request, $indicatorsIdOfSuperMasterLevel) {
            foreach ($request->post('indicators') as $key => $value) {
                if (!in_array($value, $indicatorsIdOfSuperMasterLevel)) {
                    $validator->errors()->add(
                        'indicators', "'indicator ID: $value' tidak cocok dengan kertas kerja 'level: super-master'."
                    );
                }
            }
        });

        return $validator;
    }

    public function destroyValidation(string $level, string $unit, string $year) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'level' => ['required', 'string'],
            'unit' => ['required', 'string', new ValidRequestUnitBaseOnRequestLevel($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        $validator = Validator::make($input, $attributes, $messages);

        $validator->after(function ($validator) use ($level) {
            if ($level === 'super-master') {
                $validator->errors()->add(
                    'level', sprintf("Kertas Kerja 'level: %s' tidak bisa dihapus.", $level)
                );
            }
        });

        $where = $unit === 'master' ? ['level_id' => $this->levelRepository->findIdBySlug($level), 'year' => $year] : ['level_id' => $this->levelRepository->findIdBySlug($level), 'unit_id' => $this->unitRepository->findIdBySlug($unit), 'year' => $year];

        $indicators = $this->indicatorRepository->findAllWithTargetsAndRealizationsByWhere($where);

        //cek apakah target or realization sudah ada yang di-edit
        $isDefault = true;
        foreach ($indicators as $indicator) {
            foreach ($indicator->targets as $target) {
                if (!$target->default) {
                    $isDefault = false;
                    break;
                }
            }

            if ($isDefault === false) {break;}

            foreach ($indicator->realizations as $realization) {
                if (!$realization->default) {
                    $isDefault = false;
                    break;
                }
            }
        }

        if (!$isDefault) {
            $validator->after(function ($validator) use ($level, $unit, $year) {
                $validator->errors()->add(
                    'level', sprintf("Kertas kerja 'level: %s' 'unit: %s' 'year: %s' tidak bisa dihapus, karena sudah memiliki kertas kerja target atau realisasi.", $level, $unit, $year)
                );
            });
        }

        return $validator;
    }
}
