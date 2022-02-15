<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\LevelRepository;
use App\Repositories\UnitRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UnitValidationService
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

    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string', 'not_in:super-master,master,child,super-admin,admin,data-entry,employee'],
            'parent_level' => ['required', 'string'],
            'parent_unit' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $name__lowercase = strtolower($request->post('name'));
        $parent_level = $request->post('parent_level');
        $parent_unit = $request->post('parent_unit');

        //memastikan nama yang akan di-store tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'master', 'child', 'super-admin', 'admin', 'data-entry', 'employee'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "nama sudah tersedia.");
            });
        }

        //memastikan nama yang akan di-store jika dijadikan slug belum terdaftar di DB
        $units = $this->unitRepository->find__all();
        foreach ($units as $unit) {
            if ($unit->slug === Str::slug($name__lowercase)) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('name', "nama sudah tersedia.");
                });
                break;
            }
        }

        //memastikan parent level yang akan di-store terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($parent_level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_level', "parent level tidak tersedia.");
            });
        }

        //memastikan parent unit yang akan di-store terdaftar di DB
        $result = $this->unitRepository->count__all__by__slug($parent_unit);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_unit', "parent unit tidak tersedia.");
            });
        }

        //memastikan level-slug dari parent unit yang akan di-store sama dengan parent level
        $unit = $this->unitRepository->find__with__level__by__slug($parent_unit);

        if ($unit->level->slug !== $parent_level) {
            $validator->after(function ($validator) {
                $validator->errors()->add('parent_unit', "(#6) : Anda tidak memiliki hak akses.");
            });
        }

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
