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

    public function rules(): array
    {
        return [
            'nama_pelapor'            => ['required', 'string', 'max:255'],
            'nomor_kontak'            => ['required', 'string', 'max:20'],
            'jenis_kejadian'          => ['required', 'string', 'in:Gempa Bumi,Tsunami,Tanah Longsor,Kebakaran,Banjir,Lainnya'],
            'di_lokasi_kejadian'      => ['required', 'boolean'],
            'latitude'                => ['required_if:di_lokasi_kejadian,true', 'nullable', 'numeric', 'between:-90,90'],
            'longitude'               => ['required_if:di_lokasi_kejadian,true', 'nullable', 'numeric', 'between:-180,180'],
            'alamat_lokasi'           => ['nullable', 'string', 'max:500'],
            'tanggal_kejadian'        => ['required', 'date'],
            'deskripsi'               => ['required', 'string'],
            'foto'                    => ['nullable', 'array', 'max:5'],
            'foto.*'                  => ['image', 'max:2048'],
            'wilayah_id'              => ['nullable', 'integer', 'exists:wilayah,id'],
            // Data korban
            'meninggal_jumlah'        => ['nullable', 'integer', 'min:0'],
            'meninggal_jenis_kelamin' => ['nullable', 'string', 'in:Laki-laki,Perempuan,Campuran'],
            'penyebab_meninggal'      => ['nullable', 'string'],
            'hilang_jumlah'           => ['nullable', 'integer', 'min:0'],
            'hilang_jenis_kelamin'    => ['nullable', 'string', 'in:Laki-laki,Perempuan,Campuran'],
            'luka_berat_jumlah'       => ['nullable', 'integer', 'min:0'],
            'luka_berat_jenis_kelamin'=> ['nullable', 'string', 'in:Laki-laki,Perempuan,Campuran'],
            'penyebab_luka_berat'     => ['nullable', 'string'],
            'luka_ringan_jumlah'      => ['nullable', 'integer', 'min:0'],
            'luka_ringan_jenis_kelamin'=> ['nullable', 'string', 'in:Laki-laki,Perempuan,Campuran'],
            'penyebab_luka_ringan'    => ['nullable', 'string'],
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
            'foto.max'                    => 'Maksimal 5 foto.',
            'foto.*.image'                => 'File harus berupa gambar.',
            'foto.*.max'                  => 'Ukuran foto maksimal 2MB.',
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
