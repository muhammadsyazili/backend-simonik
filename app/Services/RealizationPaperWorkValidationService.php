<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UserRepository;
use App\Rules\LevelIsThisAndChildFromUser;
use App\Rules\UnitIsThisAndChildUser;
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
            'level' => ['required', 'string', 'not_in:super-master', new LevelIsThisAndChildFromUser($user)],
            'unit' => ['required', 'string', 'not_in:master', new UnitIsThisAndChildUser($user)], //new UnitMatchWithLevel($request->query('level'))
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
}
