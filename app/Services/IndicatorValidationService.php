<?php

namespace App\Services;

use App\DTO\ConstructRequest;
use App\Repositories\IndicatorRepository;
use App\Repositories\UserRepository;
use App\Rules\GreaterThanOrSameCurrentYear;
use App\Rules\Indicator__NotHave__Childs;
use App\Rules\Indicator__IsSuperMaster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndicatorValidationService
{
    private ?UserRepository $userRepository;
    private ?IndicatorRepository $indicatorRepository;

    public function __construct(?ConstructRequest $constructRequest = null)
    {
        if (!is_null($constructRequest)) {
            $this->userRepository = $constructRequest->userRepository;
            $this->indicatorRepository = $constructRequest->indicatorRepository;
        }
    }

    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'indicator' => ['required', 'string', 'max:100'],
            'dummy' => ['required', 'boolean'],
            'reducing_factor' => ['nullable', 'required_if:dummy,0', 'boolean'],
            'polarity' => ['nullable', 'required_if:reducing_factor,0', 'in:1,-1'],
            'formula' => ['nullable', 'string'],
            'measure' => ['nullable', 'string'],
            'validity' => ['nullable'],
            'weight' => ['nullable'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_if' => ':attribute tidak boleh kosong.',
            'max' => [
                'numeric' => ':attribute tidak boleh lebih besar dari :max.',
                'file'    => ':attribute tidak boleh lebih besar dari :max kilobytes.',
                'string'  => ':attribute tidak boleh lebih besar dari :max characters.',
                'array'   => ':attribute tidak boleh lebih dari :max items.',
            ],
            'boolean' => ':attribute harus true atau false.',
            'in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
            'gte' => [
                'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
                'file'    => ':attribute harus lebih besar dari atau sama dengan :value kilobytes.',
                'string'  => ':attribute harus lebih besar dari atau sama dengan :value characters.',
                'array'   => ':attribute harus memiliki :value item atau lebih.',
            ],
        ];

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                $attributes["validity.$key"] = ['in:1,0'];
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                $attributes["weight.$key"] = ['numeric', 'gte:0'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                if (!in_array($key, ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('validity', "(#7.1) : Akses Ilegal !");
                    });
                    break;
                }
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                if (!in_array($key, ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('validity', "(#7.2) : Akses Ilegal !");
                    });
                    break;
                }
            }
        }

        return $validator;
    }

    //use repo UserRepository, IndicatorRepository
    public function editValidation(string|int $userId, string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->find__with__role_unit_level__by__id($userId);
        $year = $this->indicatorRepository->find__year__by__id($id);

        $attributes = [
            'id' => ['required', new GreaterThanOrSameCurrentYear($user, $year)],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
        ];

        $input = ['id' => $id];

        $validator = Validator::make($input, $attributes, $messages);

        return $validator;
    }

    //use repo UserRepository, IndicatorRepository
    public function updateValidation(Request $request, string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        $user = $this->userRepository->find__with__role_unit_level__by__id($request->header('X-User-Id'));
        $year = $this->indicatorRepository->find__year__by__id($id);

        $attributes = [
            'indicator' => ['required', 'string', 'max:100', new GreaterThanOrSameCurrentYear($user, $year)],
            'dummy' => ['required', 'boolean'],
            'reducing_factor' => ['nullable', 'required_if:dummy,0', 'boolean'],
            'polarity' => ['nullable', 'required_if:reducing_factor,0', 'in:1,-1'],
            'formula' => ['nullable', 'string'],
            'measure' => ['nullable', 'string'],
            'validity' => ['nullable'],
            'weight' => ['nullable'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'required_if' => ':attribute tidak boleh kosong.',
            'max' => [
                'numeric' => ':attribute tidak boleh lebih besar dari :max.',
                'file'    => ':attribute tidak boleh lebih besar dari :max kilobytes.',
                'string'  => ':attribute tidak boleh lebih besar dari :max characters.',
                'array'   => ':attribute tidak boleh lebih dari :max items.',
            ],
            'boolean' => ':attribute harus true atau false.',
            'in' => ':attribute yang dipilih tidak sah.',
            'numeric' => ':attribute harus numerik.',
            'gte' => [
                'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
                'file'    => ':attribute harus lebih besar dari atau sama dengan :value kilobytes.',
                'string'  => ':attribute harus lebih besar dari atau sama dengan :value characters.',
                'array'   => ':attribute harus memiliki :value item atau lebih.',
            ],
        ];

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                $attributes["validity.$key"] = ['in:1,0'];
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                $attributes["weight.$key"] = ['numeric', 'gte:0'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                if (!in_array($key, ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('validity', "(#7.3) : Akses Ilegal !");
                    });
                    break;
                }
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                if (!in_array($key, ['jan', 'feb', 'mar', 'apr', 'may', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'])) {
                    $validator->after(function ($validator) {
                        $validator->errors()->add('validity', "(#7.4) : Akses Ilegal !");
                    });
                    break;
                }
            }
        }

        return $validator;
    }

    public function destroyValidation(string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan indikator yang akan di-destroy ber-label super-master
        //memastikan indikator yang akan di-destroy belum memiliki turunan

        $attributes = [
            'id' => ['required', 'uuid', new Indicator__IsSuperMaster(), new Indicator__NotHave__Childs()],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
        ];

        return Validator::make(['id' => $id], $attributes, $messages);
    }
}
