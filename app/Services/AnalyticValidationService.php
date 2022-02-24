<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UserRepository;
use App\Rules\Level__IsThisAndChildFromUser__Except__Employee;
use App\Rules\Unit__IsThisAndChildUser__Except__Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class AnalyticValidationService
{
    private ?UserRepository $userRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        if (!is_null($constructRequest)) {
            $this->userRepository = $constructRequest->userRepository;
        }
    }

    //use repo UserRepository
    public function indexValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-edit sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-edit sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsThisAndChildFromUser__Except__Employee($user)],
            'unit' => ['required', 'string', 'not_in:master', new Unit__IsThisAndChildUser__Except__Employee($user)],
            'tahun' => ['required', 'string', 'date_format:Y'],
            'bulan' => ['required', 'string', 'in:1,2,3,4,5,6,7,8,9,10,11,12'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }
}
