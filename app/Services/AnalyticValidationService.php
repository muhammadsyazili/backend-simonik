<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\UserRepository;
use App\Rules\Level__IsThisAndChildFromUser__Except__Employee;
use App\Rules\Unit__IsThisAndChildUser__Except__Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class AnalyticValidationService
{
    private ?UserRepository $userRepository;
    private ?IndicatorRepository $indicatorRepository;

    public function __construct(ConstructRequest $constructRequest)
    {
        if (!is_null($constructRequest)) {
            $this->userRepository = $constructRequest->userRepository;
            $this->indicatorRepository = $constructRequest->indicatorRepository;
        }
    }

    //use repo UserRepository
    public function analyticValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan level yang akan di-edit sesuai dengan level user login saat ini atau level turunan yang diizinkan
        //memastikan unit yang akan di-edit sesuai dengan unit user login saat ini atau unit turunan yang diizinkan

        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $attributes = [
            'level' => ['required', 'string', 'not_in:super-master', new Level__IsThisAndChildFromUser__Except__Employee($user)],
            'unit' => ['required', 'string', 'not_in:master', new Unit__IsThisAndChildUser__Except__Employee($user)],
            'tahun' => ['required', 'integer', 'date_format:Y'],
            'bulan' => ['required', 'string', 'in:jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'integer' => ':attribute harus bulangan bulat.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }

    //use repo UserRepository, IndicatorRepository
    public function analyticByIdValidation(Request $request, string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));

        $indicator = $this->indicatorRepository->find__with__level_unit__by__id($id);

        $unit = is_null($indicator->unit) ? null : $indicator->unit->slug;

        $attributes = [
            'id' => ['required'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = ['id' => $id];

        $validator = Validator::make($input, $attributes, $messages);

        if (is_null($unit)) {
            $validator->after(function ($validator) {
                $validator->errors()->add('id', "(#7.1) : Akses Ilegal !");
            });
        }

        if (!is_null($user->unit)) {
            if ($user->unit->slug !== $unit) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('id', "(#7.2) : Akses Ilegal !");
                });
            }
        }

        return $validator;
    }
}
