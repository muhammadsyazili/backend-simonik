<?php

namespace App\Services;

use App\Rules\Indicator__NotHave__Childs;
use App\Rules\Indicator__IsSuperMaster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndicatorValidationService
{
    public function storeValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'indicator' => ['required', 'string', 'max:100'],
            'dummy' => ['required', 'boolean'],
            'reducing_factor' => ['nullable', 'required_if:dummy,0', 'boolean'],
            'polarity' => ['nullable', 'required_if:dummy,0', 'in:1,-1'],
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
        ];

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                $attributes["validity.$key"] = ['in:1,0'];
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                $attributes["weight.$key"] = ['numeric'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        foreach ($request->post('validity') as $key => $value) {
            if (!in_array($key, ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'])) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('validity', "(#7.1) : Akses Ilegal !");
                });
                break;
            }
        }

        foreach ($request->post('weight') as $key => $value) {
            if (!in_array($key, ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'])) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('validity', "(#7.2) : Akses Ilegal !");
                });
                break;
            }
        }

        return $validator;
    }

    public function updateValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'indicator' => ['required', 'string', 'max:100'],
            'dummy' => ['required', 'boolean'],
            'reducing_factor' => ['nullable', 'required_if:dummy,0', 'boolean'],
            'polarity' => ['nullable', 'required_if:dummy,0', 'in:1,-1'],
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
        ];

        if (!is_null($request->post('validity'))) {
            foreach ($request->post('validity') as $key => $value) {
                $attributes["validity.$key"] = ['in:1,0'];
            }
        }

        if (!is_null($request->post('weight'))) {
            foreach ($request->post('weight') as $key => $value) {
                $attributes["weight.$key"] = ['numeric'];
            }
        }

        $input = Arr::only($request->post(), array_keys($attributes));

        $validator = Validator::make($input, $attributes, $messages);

        foreach ($request->post('validity') as $key => $value) {
            if (!in_array($key, ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'])) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('validity', "(#7.3) : Akses Ilegal !");
                });
                break;
            }
        }

        foreach ($request->post('weight') as $key => $value) {
            if (!in_array($key, ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'])) {
                $validator->after(function ($validator) {
                    $validator->errors()->add('validity', "(#7.4) : Akses Ilegal !");
                });
                break;
            }
        }

        return $validator;
    }

    public function destroyValidation(string|int $id): \Illuminate\Contracts\Validation\Validator
    {
        //memastikan KPI yang akan di-destroy ber-label super-master
        //memastikan KPI yang akan di-destroy belum memiliki turunan

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
