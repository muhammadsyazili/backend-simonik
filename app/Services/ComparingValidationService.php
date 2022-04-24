<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;

class ComparingValidationService
{
    public function comparingValidation(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        $attributes = [
            'id_kiri' => ['required', 'uuid'],
            'bulan_kiri' => ['required', 'string', 'in:jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec'],

            'id_kanan' => ['required', 'uuid'],
            'bulan_kanan' => ['required', 'string', 'in:jan,feb,mar,apr,may,jun,jul,aug,sep,oct,nov,dec'],
        ];

        $messages = [
            'required' => ':attribute tidak boleh kosong.',
            'integer' => ':attribute harus bulangan bulat.',
            'date_format' => ':attribute harus berformat yyyy.',
            'not_in' => ':attribute yang dipilih tidak sah.',
            'in' => ':attribute yang dipilih tidak sah.',
        ];

        $input = Arr::only($request->query(), array_keys($attributes));

        return Validator::make($input, $attributes, $messages);
    }
}
