<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UserRepository;
use App\Rules\LevelIsThisAndChildFromUserRole;
use App\Rules\UnitIsThisAndChildUserRole;
use App\Rules\UnitMatchOnRequestLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class TargetPaperWorkValidationService {
    
    private ?UserRepository $userRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        $this->userRepository = $constructRequest->userRepository;
    }

    public function editValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->findWithRoleUnitLevelById($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', new LevelIsThisAndChildFromUserRole($user)],
            'unit' => ['required', 'string', new UnitIsThisAndChildUserRole($user), new UnitMatchOnRequestLevel($request->query('level'))],
            'tahun' => ['required', 'string', 'date_format:Y'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }
}
