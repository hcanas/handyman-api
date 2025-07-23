<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseFormRequest extends FormRequest
{
    protected function failedValidation(Validator $validator)
    {
        $errors = collect($validator->errors()->messages())
            ->map(fn($arr) => $arr[0])
            ->toArray();

        throw new HttpResponseException(response()->json([
            'message' => 'Unprocessable data',
            'errors' => $errors,
        ], 422));
    }
}
