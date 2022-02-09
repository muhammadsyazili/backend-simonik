<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserValidationService {
    private ?UserRepository $userRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->userRepository = $constructRequest->userRepository;
            $this->unitRepository = $constructRequest->unitRepository;
        }
    }

    public function indexValidation(Request $request)
    {

    }

    public function createValidation(Request $request)
    {

    }

    //use repo UserRepository, UnitRepository
    public function storeValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string'],
            'nip' => ['required', 'string'],
            'username' => ['required', 'string', 'alpha_dash', 'not_in:super-master,master,child,super-admin,admin,data-entry,employee'],
            'email' => ['required', 'string'],
            'unit' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'email' => ':attribute harus valid.',
            'alpha_dash' => ':attribute hanya boleh mengandung huruf, angka, dashes and underscores.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $username = $request->post('username');
        $unit = $request->post('unit');

        //username tidak mengandung keyword
        $validator->after(function ($validator) use ($username) {
            if (Str::containsAll(strtolower($username), ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
                $validator->errors()->add('username', "username sudah tersedia.");
            }
        });

        //username belum terdaftar di DB
        $result = $this->userRepository->count__all__by__username($username);
        $validator->after(function ($validator) use ($result) {
            if ($result > 0) {
                $validator->errors()->add('username', "username sudah tersedia.");
            }
        });

        //unit terdapat di DB
        $result = $this->unitRepository->count__all__by__slug($unit);
        $validator->after(function ($validator) use ($result) {
            if ($result === 0) {
                $validator->errors()->add('unit', "unit tidak tersedia.");
            }
        });

        return $validator;
    }

    public function editValidation(Request $request)
    {

    }

    public function updateValidation(Request $request)
    {

    }

    public function destroyValidation(Request $request)
    {

    }
}
