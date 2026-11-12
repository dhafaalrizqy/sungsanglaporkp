<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * Catatan: untuk file, max dihitung dalam KB (2048 KB = 2 MB).
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resident_id' => auth()->user()->hasRole('resident') ? 'nullable|exists:residents,id' : 'required|exists:residents,id',
            'report_category_id' => 'required|exists:report_categories,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:jpeg,jpg,png|max:2048',
            'latitude' => 'required|string',
            'longitude' => 'required|string',
            'address' => 'required|string',
        ];
    }

    /**
     * Pesan error dalam Bahasa Indonesia.
     */
    public function messages(): array
    {
        return [
            'resident_id.required' => 'Data pelapor wajib diisi.',
            'resident_id.exists' => 'Data pelapor tidak valid.',

            'report_category_id.required' => 'Kategori laporan wajib dipilih.',
            'report_category_id.exists' => 'Kategori laporan tidak valid.',

            'title.required' => 'Judul laporan wajib diisi.',
            'title.max' => 'Judul laporan maksimal 255 karakter.',

            'description.required' => 'Cerita laporan wajib diisi.',

            'image.required' => 'Bukti laporan (foto) wajib diambil.',
            'image.image' => 'Bukti laporan harus berupa gambar.',
            'image.mimes' => 'Format foto harus jpeg, jpg, atau png.',
            'image.max' => 'Ukuran foto maksimal 2 MB.',
            'image.uploaded' => 'Foto gagal diunggah. Ukuran foto maksimal 2 MB.',

            'latitude.required' => 'Lokasi laporan wajib dipilih di peta.',
            'longitude.required' => 'Lokasi laporan wajib dipilih di peta.',

            'address.required' => 'Alamat lengkap wajib diisi.',
        ];
    }
}