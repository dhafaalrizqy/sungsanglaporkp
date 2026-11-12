@extends('layouts.no-nav')

@section('title', "Daftar")

@section('content')

{{-- Logo Polres Sungsang --}}
<div class="d-flex flex-column align-items-center mt-4 mb-3">
    <img src="{{ asset('assets/app/images/logo-polres-sungsang.png') }}" 
         alt="Logo Polres Sungsang" 
         style="width: 90px; height: 90px; object-fit: contain;">
    <h6 class="fw-bold mt-2 text-center" style="color: var(--primary);">POLSEK BANYUASIN II SUNGSANG</h6>
</div>

<hr class="my-2">

<h5 class="fw-bold mt-3">Daftar sebagai pengguna baru</h5>
    <p class="text-muted mt-2">Silahkan mengisi form dibawah ini untuk mendaftar</p>

        <form action="{{ route('register.store') }}" method="POST" class="mt-4" enctype="multipart/form-data">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email"
                    value="{{ old('email') }}">

                @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="name" class="form-label">Nama Lengkap</label>
                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name"
                    value="{{ old('name') }}">

                @error('name')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="avatar" class="form-label">Foto Profil</label>
                <input type="file" class="form-control @error('avatar') is-invalid @enderror" id="avatar" name="avatar"
                    accept="image/png, image/jpeg">
                <div class="form-text">maksimal 2 MB.</div>

                @error('avatar')
                <div class="invalid-feedback d-block">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" 
                    name="password">

                @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            {{-- Konfirmasi Password (BARU) --}}
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
                <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror"
                    id="password_confirmation" name="password_confirmation">

                @error('password_confirmation')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
                @enderror
            </div>

            <button class="btn btn-primary w-100 mt-2" type="submit" color="primary" id="btn-login">
                Daftar
            </button>

            <div class="d-flex justify-content-between mt-3">
                <a href="{{ route('login') }}" class="text-decoration-none text-primary">Sudah punya akun?</a>
            </div>
        </form>

{{-- Cek ukuran foto profil di browser (maks 2 MB). Validasi utama tetap di controller. --}}
<script>
    document.getElementById('avatar').addEventListener('change', function () {
        const file = this.files[0];
        const maxSize = 2 * 1024 * 1024; // 2 MB

        if (file && file.size > maxSize) {
            alert('Ukuran foto profil maksimal 2 MB.');
            this.value = ''; // kosongkan pilihan file
        }
    });
</script>
@endsection
