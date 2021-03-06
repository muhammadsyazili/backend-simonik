<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
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
    private ?IndicatorRepository $indicatorRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->levelRepository = $constructRequest->levelRepository;
            $this->unitRepository = $constructRequest->unitRepository;
            $this->indicatorRepository = $constructRequest->indicatorRepository;
        }
    }

    public function indexValidation(Request $request)
    {
    }

    public function createValidation(Request $request)
    {
    }

    //use repo LevelRepository, UnitRepository
    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string'],
            'level' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $level = $request->post('level');
        $parent_unit = $request->post('parent_unit');
        $name__lowercase = strtolower($request->post('name'));
        $level__lowercase = strtolower($request->post('level'));

        //memastikan nama yang akan di-store tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'super master', 'master'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "(#1.1) : Nama Unit Kerja Sudah Tersedia.");
            });
        }

        //memastikan nama yang akan di-store jika dijadikan slug belum terdaftar di DB
        $units = $this->unitRepository->find__all();
        foreach ($units as $unit) {
            if ($unit->slug === Str::slug("$level__lowercase-$name__lowercase")) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('name', "(#1.2) : Nama Unit Kerja Sudah Tersedia.");
                });
                break;
            }
        }

        //memastikan level yang akan di-store terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('level', "(#1.1) : Level Belum Tersedia.");
            });
        }

        $level = $this->levelRepository->find__with__parent__by__slug($level);
        $units = $this->unitRepository->find__all__by__levelId($level->parent->id);

        if (count($units) !== 0) {
            //memastikan parent unit yang akan di-store terdaftar di DB
            $result = $this->unitRepository->count__all__by__slug($parent_unit);
            if ($result === 0) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_unit', "(#1.1) : Parent Unit Kerja Belum Tersedia.");
                });
            }

            //memastikan level yang akan di-store merupakan parent level yang ada di parent unit
            $isAvailable = false;
            foreach ($units as $unit) {
                if ($unit->slug === $parent_unit) {
                    $isAvailable = true;
                    break;
                }
            }

            if (!$isAvailable) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_unit', "(#6.1) : Akses Ilegal !");
                });
            }
        }

        return $validator;
    }

    public function editValidation(Request $request)
    {
    }

    //use repo LevelRepository, UnitRepository
    public function updateValidation(Request $request, string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'name' => ['required', 'string'],
            'level' => ['required', 'string'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        $level = $request->post('level');
        $parent_unit = $request->post('parent_unit');
        $name__lowercase = strtolower($request->post('name'));
        $name__uppercase = strtoupper($request->post('name'));
        $level__lowercase = strtolower($request->post('level'));

        $unit = $this->unitRepository->find__by__id($id);

        //memastikan nama yang akan di-update tidak mengandung keyword
        if (Str::containsAll($name__lowercase, ['super-master', 'super master', 'master'])) {
            $validator->after(function ($validator) {
                $validator->errors()->add('name', "(#1.3) : Nama Unit Kerja Sudah Tersedia.");
            });
        }

        //nama unit diubah
        if ($unit->name !== $name__uppercase) {
            //memastikan nama yang akan di-update jika dijadikan slug belum terdaftar di DB
            $units = $this->unitRepository->find__all();
            foreach ($units as $unit) {
                if ($unit->slug === Str::slug("$level__lowercase-$name__lowercase")) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('name', "(#1.4) : Nama Unit Kerja Sudah Tersedia.");
                    });
                    break;
                }
            }
        }

        //memastikan level yang akan di-update terdaftar di DB
        $result = $this->levelRepository->count__all__by__slug($level);
        if ($result === 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('level', "(#1.2) : Level Belum Tersedia.");
            });
        }

        $level = $this->levelRepository->find__with__parent__by__slug($level);
        $units = $this->unitRepository->find__all__by__levelId($level->parent->id);

        if (count($units) !== 0) {
            //memastikan parent unit yang akan di-update bukan merupakan unit yang saat ini di-update
            if ($unit->slug === $parent_unit) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_unit', "(#1.1) : Tidak Diizinkan Memilih Parent Yang Sama Dengan Unit Kerja.");
                });
            }

            //memastikan parent unit yang akan di-update terdaftar di DB
            $result = $this->unitRepository->count__all__by__slug($parent_unit);
            if ($result === 0) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_unit', "(#1.2) : Parent Unit Kerja Belum Tersedia.");
                });
            }

            //memastikan level yang akan di-update merupakan parent level yang ada di parent unit
            $isAvailable = false;
            foreach ($units as $unit) {
                if ($unit->slug === $parent_unit) {
                    $isAvailable = true;
                    break;
                }
            }

            if (!$isAvailable) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('parent_unit', "(#6.2) : Akses Ilegal !");
                });
            }
        }

        return $validator;
    }

    //use repo IndicatorRepository
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

        //memastikan unit yang akan dihapus belum memiliki kertas kerja indikator
        $result = $this->indicatorRepository->count__all__by__unitId($id);
        if ($result !== 0) {
            $validator->after(function ($validator) {
                $validator->errors()->add('id', "(#1.1) : Unit Kerja Tidak Bisa Dihapus, Karena Sudah Memiliki Kertas Kerja Indikator.");
            });
        }

        return $validator;
    }
}