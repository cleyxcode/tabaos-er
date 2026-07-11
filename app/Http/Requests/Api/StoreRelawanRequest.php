<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreRelawanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nik'         => ['required', 'string', 'max:20'],
            'alamat'      => ['required', 'string', 'max:500'],
            'keahlian'    => ['nullable', 'string', 'max:255'],
            'organisasi'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required'    => 'NIK wajib diisi.',
            'alamat.required' => 'Alamat wajib diisi.',
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
