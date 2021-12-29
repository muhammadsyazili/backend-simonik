<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UserRepository;
use App\Rules\HasIndicatorIdNotMatchOnPaperWork;
use App\Rules\HasTargetAndRealization;
use App\Rules\IsSuperMasterPaperWork;
use App\Rules\LevelIsChildFromUserRole;
use App\Rules\LevelIsThisAndChildFromUserRole;
use App\Rules\PaperWorkAvailable;
use App\Rules\UnitMatchOnRequestLevel;
use App\Rules\UnitIsThisAndChildUserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class IndicatorPaperWorkValidationService {
    private ?UserRepository $userRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
    }

    //use repo UserRepository
    public function indexValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new LevelIsThisAndChildFromUserRole($user)],
            'unit' => ['required_unless:level,super-master', 'string', new UnitIsThisAndChildUserRole($user), new UnitMatchOnRequestLevel($request->query('level'))],
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
        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'indicators' => ['required', new HasIndicatorIdNotMatchOnPaperWork($request->post('indicators'))],
            'level' => ['required', 'string', new LevelIsChildFromUserRole($user), new PaperWorkAvailable($request->post('level'), $request->post('year'))],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }

    public function destroyValidation(string $level, string $unit, string $year) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'level' => ['required', 'string', new IsSuperMasterPaperWork(), new HasTargetAndRealization($level, $unit, $year)],
            'unit' => ['required', 'string', new UnitMatchOnRequestLevel($level)],
            'year' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = ['level' => $level, 'unit' => $unit, 'year' => $year];

        return Validator::make($input, $attributes, $messages);
    }
}
