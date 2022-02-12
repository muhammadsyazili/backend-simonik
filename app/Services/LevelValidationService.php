<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LevelValidationService
{
    private ?LevelRepository $levelRepository;
    private ?UnitRepository $unitRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->levelRepository = $constructRequest->levelRepository;
            $this->unitRepository = $constructRequest->unitRepository;
        }
    }

    public function indexValidation(Request $request)
    {
    }

    public function createValidation(Request $request)
    {
    }

    //use repo LevelRepository
    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string', 'not_in:super-master,master,child,super-admin,admin,data-entry,employee'],
            'parent_level' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $name__lowercase = strtolower($request->post('name'));
        $parent_level = $request->post('parent_level');

        //memastikan nama yang akan di-store tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "nama sudah tersedia.");
            });
        }

        //memastikan nama yang akan di-store jika dijadikan slug belum terdaftar di DB
        $levels = $this->levelRepository->find__all();
        foreach ($levels as $level) {
            if ($level->slug === Str::slug($name__lowercase)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('name', "nama sudah tersedia.");
                });
                break;
            }
        }

        //memastikan parent level  yang akan di-store terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_level', "parent level tidak tersedia.");
            });
        }

        return $validator;
    }

    public function editValidation(Request $request)
    {
    }

    //use repo LevelRepository
    public function updateValidation(Request $request, string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string', 'not_in:super-master,master,child,super-admin,admin,data-entry,employee'],
            'parent_level' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $name__lowercase = strtolower($request->post('name'));
        $parent_level = $request->post('parent_level');

        $level = $this->levelRepository->find__by__id($id);

        //memastikan nama yang akan di-update tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "nama sudah tersedia.");
            });
        }

        //nama level diubah
        if (strtolower($level->name) !== $name__lowercase) {
            //memastikan nama yang akan di-update jika dijadikan slug belum terdaftar di DB
            $levels = $this->levelRepository->find__all();
            foreach ($levels as $level) {
                if ($level->slug === Str::slug($name__lowercase)) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('name', "nama sudah tersedia.");
                    });
                    break;
                }
            }
        }

        //memastikan parent level  yang akan di-update terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_level', "parent level tidak tersedia.");
            });
        }

        return $validator;
    }

    //use repo UnitRepository
    public function destroyValidation(string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'id' => ['required', 'integer'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'integer' => ':attribute tidak valid.',
        ];

        $validator = Validator::make(['id' => $id], $attributes, $messages);

        //memastikan level yang akan dihapus belum memiliki unit
        $result = $this->unitRepository->count__all__by__levelId($id);
        if ($result !== 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('id', "level tidak bisa dihapus.");
            });
        }

        return $validator;
    }
}
