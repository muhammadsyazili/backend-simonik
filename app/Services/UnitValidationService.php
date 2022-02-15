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
            'level' => ['required', 'string'],
            'parent_unit' => ['string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'not_in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $name__lowercase = strtolower($request->post('name'));
        $level = $request->post('level');
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

        //memastikan level yang akan di-store terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('level', "level tidak tersedia.");
            });
        }

        $level = $this->levelRepository->find__with__parent__by__slug($level);
        $units = $this->unitRepository->find__all__by__levelId($level->parent->id);

        if (count($units) !== 0) {
            //memastikan parent unit yang akan di-store terdaftar di DB
            $result = $this->unitRepository->count__all__by__slug($parent_unit);
            if ($result === 0) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_unit', "unit tidak tersedia.");
                });
            }

            //memastikan level yang akan di-store merupakan turunan dari level yang ada di parent unit
            foreach ($units as $unit) {
                if ($unit->slug !== $parent_unit) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('parent_unit', "(#6.1) : Akses ilegal !");
                    });
                }
            }
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
