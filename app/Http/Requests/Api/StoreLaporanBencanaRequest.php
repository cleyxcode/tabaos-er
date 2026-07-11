<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreLaporanBencanaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Isi nama_pelapor dan nomor_kontak otomatis dari data user jika
     * tidak dikirim oleh client (Flutter tidak wajib mengirimnya).
     */
    protected function prepareForValidation(): void
    {
        $user = auth('pengguna')->user();

        if ($user) {
            if (empty($this->nama_pelapor)) {
                $this->merge(['nama_pelapor' => $user->name]);
            }
            if (empty($this->nomor_kontak)) {
                $this->merge(['nomor_kontak' => $user->phone]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'nama_pelapor'             => ['required', 'string', 'max:255'],
            'nomor_kontak'             => ['required', 'string', 'max:20'],
            'jenis_kejadian'           => [
                'required', 'string',
                'in:Gempa Bumi,Tsunami,Tanah Longsor,Kebakaran,Banjir,Angin Puting Beliung,Lainnya',
            ],
            'di_lokasi_kejadian'       => ['required', 'boolean'],
            'latitude'                 => ['required_if:di_lokasi_kejadian,true', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'                => ['required_if:di_lokasi_kejadian,true', 'nullable', 'numeric', 'between:-180,180'],
            'alamat_lokasi'            => ['nullable', 'string', 'max:500'],
            'tanggal_kejadian'         => ['required', 'date'],
            'deskripsi'                => ['required', 'string'],
            'foto'                     => ['nullable', 'array', 'max:10'],
            'foto.*'                   => ['image', 'max:4096'],
            'wilayah_id'               => ['nullable', 'integer', 'exists:wilayah,id'],
            // Data korban (semua opsional, default 0)
            'meninggal_jumlah'         => ['nullable', 'integer', 'min:0'],
            'luka_berat_jumlah'        => ['nullable', 'integer', 'min:0'],
            'luka_ringan_jumlah'       => ['nullable', 'integer', 'min:0'],
            'hilang_jumlah'            => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_pelapor.required'       => 'Nama pelapor wajib diisi.',
            'nomor_kontak.required'       => 'Nomor kontak wajib diisi.',
            'jenis_kejadian.required'     => 'Jenis kejadian wajib dipilih.',
            'jenis_kejadian.in'           => 'Jenis kejadian tidak valid.',
            'di_lokasi_kejadian.required' => 'Status lokasi wajib diisi.',
            'latitude.required_if'        => 'Latitude wajib diisi jika berada di lokasi kejadian.',
            'longitude.required_if'       => 'Longitude wajib diisi jika berada di lokasi kejadian.',
            'tanggal_kejadian.required'   => 'Tanggal kejadian wajib diisi.',
            'deskripsi.required'          => 'Deskripsi kejadian wajib diisi.',
            'foto.max'                    => 'Maksimal 10 foto.',
            'foto.*.image'                => 'File harus berupa gambar.',
            'foto.*.max'                  => 'Ukuran foto maksimal 4MB.',
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
