<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $penggunaId = auth('pengguna')->id();

        return [
            'name'  => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'required', 'string', 'max:20',
                        "unique:pengguna,phone,{$penggunaId}"],
            'email' => ['sometimes', 'nullable', 'email', 'max:255',
                        "unique:pengguna,email,{$penggunaId}"],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Data yang dikirim tidak valid.',
            'errors'  => $validator->errors(),
        ], 422));
    }
}
