<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreResidentRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
            'avatar' => 'required|image|mimes:jpg,jpeg,png|max:2048' // maks 2 MB (2048 KB)
        ];
    }

    public function messages(): array
    {
        return [
            'password.required'  => 'Password wajib diisi.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password.',

            'avatar.required'    => 'Foto profil wajib diisi.',
            'avatar.image'       => 'File harus berupa gambar.',
            'avatar.mimes'       => 'Format foto harus JPG, JPEG, atau PNG.',
            'avatar.max'         => 'Ukuran foto profil maksimal 2 MB.',
            'avatar.uploaded'    => 'Foto gagal diunggah, ukuran file melebihi batas server.',
        ];
    }
}