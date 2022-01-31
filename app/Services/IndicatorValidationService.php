<?php

namespace App\Services;

use App\Rules\IndicatorNotHaveChilds;
use App\Rules\IndicatorIsSuperMaster;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class IndicatorValidationService {
    public function storeValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
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

        return Validator::make($input, $attributes, $messages);
    }

    public function updateValidation(Request $request) : \Illuminate\Contracts\Validation\Validator
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

        return Validator::make($input, $attributes, $messages);
    }

    public function destroyValidation(string|int $id) : \Illuminate\Contracts\Validation\Validator
    {
        //memastikan KPI berlabel super-master
        //memastikan KPI belum memiliki turunan

        $attributes = [
            'id' => ['required', 'uuid', new IndicatorIsSuperMaster(), new IndicatorNotHaveChilds()],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'uuid' => ':attribute harus UUID format.',
        ];

        return Validator::make(['id' => $id], $attributes, $messages);
    }
}
