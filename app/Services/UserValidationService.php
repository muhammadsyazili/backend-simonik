<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserValidationService
{
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
    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string'],
            'nip' => ['required', 'string'],
            'username' => ['required', 'string', 'alpha_dash'],
            'email' => ['required', 'string'],
            'unit' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'email' => ':attribute harus valid.',
            'alpha_dash' => ':attribute hanya boleh mengandung huruf, angka, dashes (-) and underscores (_).',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $username__lowercase = strtolower($request->post('username'));
        $unit = $request->post('unit');

        //memastikan username yang akan di-store tidak mengandung keyword
        if (Str::containsAll($username__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('username', "(#1.1) : Username Sudah Tersedia.");
            });
        }

        //memastikan username yang akan di-store belum terdaftar di DB
        $users = $this->userRepository->find__all();
        foreach ($users as $user) {
            if (strtolower($user->username) === $username__lowercase) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('username', "(#1.2) : Username Sudah Tersedia.");
                });
                break;
            }
        }

        //memastikan unit yang akan di-store terdapat di DB
        $result = $this->unitRepository->count__all__by__slug($unit);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('unit', "(#1.1) : Unit Kerja Belum Tersedia.");
            });
        }

        return $validator;
    }

    public function editValidation(Request $request)
    {
    }

    //use repo UserRepository, UnitRepository
    public function updateValidation(Request $request, string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string'],
            'nip' => ['required', 'string'],
            'username' => ['required', 'string', 'alpha_dash'],
            'email' => ['required', 'string'],
            'unit' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'email' => ':attribute harus valid.',
            'alpha_dash' => ':attribute hanya boleh mengandung huruf, angka, dashes (-) and underscores (_).',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $username__lowercase = strtolower($request->post('username'));
        $unit = $request->post('unit');

        $user = $this->userRepository->find__with__role__by__id($id);

        //memastikan user yang akan di-update role-nya 'employee'
        if ($user->role->name !== 'employee') {
            $validator->after(function ($validator) {
                $validator->errors()->add('username', "(#1.1) : User Tidak Diizinkan Diubah.");
            });
        }

        //memastikan username yang akan di-update tidak mengandung keyword
        if (Str::containsAll($username__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('username', "(#1.3) : Username Sudah Tersedia.");
            });
        }

        //username diubah
        if (strtolower($user->username) !== $username__lowercase) {
            //memastikan username yang akan di-update belum terdaftar di DB
            $users = $this->userRepository->find__all();
            foreach ($users as $user) {
                if (strtolower($user->username) === $username__lowercase) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('username', "(#1.4) : Username Sudah Tersedia.");
                    });
                    break;
                }
            }
        }

        //memastikan unit yang akan di-update terdapat di DB
        $result = $this->unitRepository->count__all__by__slug($unit);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('unit', "(#1.2) : Unit Kerja Belum Tersedia.");
            });
        }

        return $validator;
    }

    //use repo UserRepository
    public function destroyValidation(string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'id' => ['required', 'uuid'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
        ];

        $validator = Validator::make(['id' => $id], $attributes, $messages);

        $user = $this->userRepository->find__with__role__by__id($id);

        //memastikan user yang akan di-destroy role-nya 'employee'
        if ($user->role->name !== 'employee') {
            $validator->after(function ($validator) {
                $validator->errors()->add('id', "(#1.1) : User Tidak Diizinkan Dihapus.");
            });
        }

        return $validator;
    }
}
