<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LevelValidationService
{
    private ?LevelRepository $levelRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->levelRepository = $constructRequest->levelRepository;
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

        //memastikan nama tidak mengandung keyword
        $validator->after(function ($validator) use ($name__lowercase) {
            if (Str::containsAll($name__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
                $validator->errors()->add('name', "nama sudah tersedia.");
            }
        });

        //memastikan nama jika dijadikan slug belum terdaftar di DB
        $levels = $this->levelRepository->find__all();
        $validator->after(function ($validator) use ($levels, $name__lowercase) {
            foreach ($levels as $level) {
                if ($level->slug === Str::slug($name__lowercase)) {
                    $validator->errors()->add('name', "nama sudah tersedia.");
                    break;
                }
            }
        });

        //memastikan parent level terdapat di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        $validator->after(function ($validator) use ($result) {
            if ($result === 0) {
                $validator->errors()->add('parent_level', "parent level tidak tersedia.");
            }
        });

        return $validator;
    }

    public function editValidation(Request $request)
    {
    }

    //use repo LevelRepository
    public function updateValidation(Request $request, string|int $id) : \Illuminate\Contracts\Validation\Validator
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

        //memastikan nama tidak mengandung keyword
        $validator->after(function ($validator) use ($name__lowercase) {
            if (Str::containsAll($name__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
                $validator->errors()->add('name', "nama sudah tersedia.");
            }
        });

        //nama level diubah
        if (strtolower($level->name) !== $name__lowercase) {
            //memastikan nama jika dijadikan slug belum terdaftar di DB
            $levels = $this->levelRepository->find__all();
            $validator->after(function ($validator) use ($levels, $name__lowercase) {
                foreach ($levels as $level) {
                    if ($level->slug === Str::slug($name__lowercase)) {
                        $validator->errors()->add('name', "nama sudah tersedia.");
                        break;
                    }
                }
            });
        }

        //memastikan parent level terdapat di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        $validator->after(function ($validator) use ($result) {
            if ($result === 0) {
                $validator->errors()->add('parent_level', "parent level tidak tersedia.");
            }
        });

        return $validator;
    }

    public function destroyValidation(Request $request)
    {
    }
}
