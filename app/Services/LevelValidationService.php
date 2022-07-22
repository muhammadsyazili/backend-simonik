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
            'name' => ['required', 'string'],
            'parent_level' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $name__lowercase = strtolower($request->post('name'));
        $parent_level = $request->post('parent_level');

        //memastikan nama yang akan di-store tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'super master'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "(#1.1) : Nama Level Sudah Tersedia.");
            });
        }

        //memastikan nama yang akan di-store jika dijadikan slug belum terdaftar di DB
        $levels = $this->levelRepository->find__all();
        foreach ($levels as $level) {
            if ($level->slug === Str::slug($name__lowercase)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('name', "(#1.2) : Nama Level Sudah Tersedia.");
                });
                break;
            }
        }

        //memastikan parent level yang akan di-store terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_level', "(#1.1) : Parent Level Belum Tersedia.");
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
            'name' => ['required', 'string'],
            'parent_level' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $name__lowercase = strtolower($request->post('name'));
        $parent_level = $request->post('parent_level');

        $level = $this->levelRepository->find__by__id($id);

        //memastikan nama yang akan di-update tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'super master'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "(#1.3) : Nama Level Sudah Tersedia.");
            });
        }

        //nama level diubah
        if (strtolower($level->name) !== $name__lowercase) {
            //memastikan nama yang akan di-update jika dijadikan slug belum terdaftar di DB
            $levels = $this->levelRepository->find__all();
            foreach ($levels as $level) {
                if ($level->slug === Str::slug($name__lowercase)) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('name', "(#1.4) : Nama Level Sudah Tersedia.");
                    });
                    break;
                }
            }
        }

        //memastikan parent level yang akan di-update bukan merupakan level yang saat ini di-update
        if ($level->slug === $parent_level) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_level', "(#1.1) : Tidak Diizinkan Memilih Parent Yang Sama Dengan Level.");
            });
        }

        //memastikan parent level yang akan di-update terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_level', "(#1.2) : Parent Level Belum Tersedia.");
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
                $validator->errors()->add('id', "(#1.1) : Level Tidak Bisa Dihapus, Karena Sudah Memiliki Unit Kerja.");
            });
        }

        return $validator;
    }
}