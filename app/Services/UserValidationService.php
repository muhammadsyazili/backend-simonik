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
            'username' => ['required', 'string', 'alpha_dash', 'not_in:super-master,master,child,super-admin,admin,data-entry,employee'],
            'email' => ['required', 'string'],
            'unit' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'email' => ':attribute harus valid.',
            'alpha_dash' => ':attribute hanya boleh mengandung huruf, angka, dashes (-) and underscores (_).',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $username = strtolower($request->post('username'));
        $unit = $request->post('unit');

        //memastikan username yang akan di-store tidak mengandung keyword
        if (Str::containsAll($username, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('username', "username sudah tersedia.");
            });
        }

        //memastikan username yang akan di-store belum terdaftar di DB
        $users = $this->userRepository->find__all();
        foreach ($users as $user) {
            if (strtolower($user->username) === $username) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('username', "username sudah tersedia.");
                });
                break;
            }
        }

        //memastikan unit yang akan di-store terdapat di DB
        $result = $this->unitRepository->count__all__by__slug($unit);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('unit', "unit tidak tersedia.");
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
            'username' => ['required', 'string', 'alpha_dash', 'not_in:super-master,master,child,super-admin,admin,data-entry,employee'],
            'email' => ['required', 'string'],
            'unit' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'email' => ':attribute harus valid.',
            'alpha_dash' => ':attribute hanya boleh mengandung huruf, angka, dashes (-) and underscores (_).',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $username = strtolower($request->post('username'));
        $unit = $request->post('unit');

        $user = $this->userRepository->find__with__role__by__id($id);

        //memastikan user yang akan di-update role-nya 'employee'
        if ($user->role->name !== 'employee') {
            $validator->after(function ($validator) {
                $validator->errors()->add('username', "user tidak bisa diubah.");
            });
        }

        //memastikan username yang akan di-update tidak mengandung keyword
        if (Str::containsAll($username, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('username', "username sudah tersedia.");
            });
        }

        //username diubah
        if ($user->username !== $username) {
            //memastikan username yang akan di-update belum terdaftar di DB
            $users = $this->userRepository->find__all();
            foreach ($users as $user) {
                if (strtolower($user->username) === $username) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('username', "username sudah tersedia.");
                    });
                    break;
                }
            }
        }

        //memastikan unit yang akan di-update terdapat di DB
        $result = $this->unitRepository->count__all__by__slug($unit);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('unit', "unit tidak tersedia.");
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
                $validator->errors()->add('id', "user tidak bisa dihapus.");
            });
        }

        return $validator;
    }
}
