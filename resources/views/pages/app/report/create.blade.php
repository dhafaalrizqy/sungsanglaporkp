@extends('layouts.no-nav')

@section('title', 'Tambah Laporan')

@section('content')
    <h3 class="mb-3">Laporkan segera masalahmu di sini!</h3>

    <p class="text-description">
        Isi form dibawah ini dengan baik dan benar sehingga kami dapat memvalidasi dan menangani laporan anda secepatnya
    </p>

    <form action="{{ route('report.store') }}" method="POST" class="mt-4" enctype="multipart/form-data" id="report-form">
        @csrf
        <input type="hidden" id="lat" name="latitude" value="{{ old('latitude') }}">
        <input type="hidden" id="lng" name="longitude" value="{{ old('longitude') }}">

        {{-- Judul --}}
        <div class="mb-3">
            <label for="title" class="form-label">Judul Laporan</label>
            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                value="{{ old('title') }}">

            @error('title')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Kategori --}}
        <div class="mb-3">
            <label for="report_category_id" class="form-label">Kategori Laporan</label>
            <select name="report_category_id" id="report_category_id"
                class="form-select @error('report_category_id') is-invalid @enderror">
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @if (old('report_category_id') == $category->id) selected @endif>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            @error('report_category_id')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Bukti Laporan (foto dari kamera, diambil dari localStorage) --}}
        <div class="mb-3">
            <label for="image" class="form-label">Bukti Laporan</label>
            <input type="file" class="form-control @error('image') is-invalid @enderror" id="image" name="image"
                accept="image/*" style="display: none">
            <img alt="image" id="image-preview" class="img-fluid rounded-2 mb-2 border">

            {{-- Info ukuran foto --}}
            <small id="image-info" class="d-block mb-3 text-muted"></small>

            @error('image')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Deskripsi --}}
        <div class="mb-3">
            <label for="description" class="form-label">Ceritakan Laporan Kamu</label>
            <textarea class="form-control @error('description') is-invalid @enderror" id="description"
                name="description" rows="5">{{ old('description') }}</textarea>

            @error('description')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        {{-- Peta --}}
        <div class="mb-3">
            <label for="map" class="form-label">Lokasi Laporan</label>
            <style>
                #map { height: 320px; width: 100%; border-radius: .5rem; border: 1px solid #dee2e6; }
            </style>
            <div id="map"></div>
            <small class="d-block mt-1 text-muted">Klik peta atau geser pin untuk menentukan lokasi.</small>

            @error('latitude')
                <small class="d-block text-danger">{{ $message }}</small>
            @enderror
        </div>

        {{-- Alamat --}}
        <div class="mb-3">
            <label for="address" class="form-label">Alamat Lengkap</label>
            <textarea class="form-control @error('address') is-invalid @enderror" id="address" name="address"
                rows="5">{{ old('address') }}</textarea>

            @error('address')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <button class="btn btn-primary w-100 mt-2" type="submit" id="btn-submit">
            Laporkan
        </button>
    </form>
@endsection

@section('scripts')
    {{-- Leaflet (peta OpenStreetMap, gratis, tanpa API key) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        // Batas ukuran foto: 2 MB
        const MAX_SIZE = 2 * 1024 * 1024;

        const imageInput   = document.getElementById('image');
        const imagePreview = document.getElementById('image-preview');
        const imageInfo    = document.getElementById('image-info');
        const btnSubmit    = document.getElementById('btn-submit');
        const reportForm   = document.getElementById('report-form');

        // Foto hasil kamera yang disimpan (base64) oleh halaman sebelumnya
        const imageBase64 = localStorage.getItem('image');

        // Ubah base64 (data URL) menjadi Blob
        function base64ToBlob(base64) {
            const parts = base64.split(',');
            const mime  = parts[0].match(/:(.*?);/)[1]; // ambil tipe asli, mis. image/jpeg
            const byteString = atob(parts[1]);
            const ab = new ArrayBuffer(byteString.length);
            const ia = new Uint8Array(ab);

            for (let i = 0; i < byteString.length; i++) {
                ia[i] = byteString.charCodeAt(i);
            }

            return new Blob([ab], { type: mime });
        }

        // Format ukuran: KB atau MB
        function formatSize(bytes) {
            if (bytes >= 1024 * 1024) {
                return (bytes / 1024 / 1024).toFixed(2) + ' MB';
            }
            return Math.round(bytes / 1024) + ' KB';
        }

        // Tampilkan pesan di bawah foto dan atur tombol kirim
        function showInfo(message, isError) {
            imageInfo.textContent = message;
            imageInfo.classList.toggle('text-danger', isError);
            imageInfo.classList.toggle('text-muted', !isError);
            btnSubmit.disabled = isError;
        }

        // Set file ke input file + preview + cek ukuran
        function setFileInputFromBase64(base64) {
            const blob = base64ToBlob(base64);
            const ext  = blob.type.split('/')[1] || 'jpg';
            const file = new File([blob], 'image.' + ext, { type: blob.type });

            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            imageInput.files = dataTransfer.files;

            imagePreview.src = URL.createObjectURL(file);

            if (file.size > MAX_SIZE) {
                showInfo(
                    'Ukuran foto ' + formatSize(file.size) + ' melebihi batas 2 MB. Silakan ambil ulang foto.',
                    true
                );
            } else {
                showInfo('Ukuran foto: ' + formatSize(file.size) + ' (maksimal 2 MB)', false);
            }
        }

        // Jalankan
        if (imageBase64) {
            try {
                setFileInputFromBase64(imageBase64);
            } catch (e) {
                console.error(e);
                showInfo('Foto tidak bisa dibaca. Silakan ambil ulang foto.', true);
            }
        } else {
            imagePreview.style.display = 'none';
            showInfo('Belum ada foto. Silakan ambil foto terlebih dahulu.', true);
        }

        // Pengaman terakhir sebelum form dikirim
        reportForm.addEventListener('submit', function (e) {
            const file = imageInput.files[0];

            if (!file || file.size > MAX_SIZE) {
                e.preventDefault();
                showInfo('Foto wajib ada dan ukurannya maksimal 2 MB.', true);
            }
        });
    </script>
    <script>
        // Peta lokasi laporan: isi input lat, lng, dan alamat
        (function () {
            const mapEl = document.getElementById('map');

            // Berhenti kalau Leaflet belum termuat atau peta sudah dibuat oleh script lain
            if (!mapEl || typeof L === 'undefined' || mapEl._leaflet_id) return;

            const latInput = document.getElementById('lat');
            const lngInput = document.getElementById('lng');
            const addressInput = document.getElementById('address');

            // Titik awal peta sebelum lokasi dipilih (ubah sesuai wilayah layananmu)
            const START = [-2.9761, 104.7754];

            const map = L.map('map').setView(START, 14);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            let marker = null;
            let addressEdited = addressInput.value.trim() !== '';
            addressInput.addEventListener('input', function () { addressEdited = true; });

            async function fillAddress(latlng) {
                if (addressEdited) return;
                try {
                    const url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&accept-language=id'
                        + '&lat=' + latlng.lat + '&lon=' + latlng.lng;
                    const res = await fetch(url);
                    const data = await res.json();
                    if (data.display_name && !addressEdited) {
                        addressInput.value = data.display_name;
                    }
                } catch (e) {
                    console.error('Gagal mengambil alamat:', e);
                }
            }

            function setPosition(latlng, withAddress) {
                if (!marker) {
                    marker = L.marker(latlng, { draggable: true }).addTo(map);
                    marker.on('dragend', function () { setPosition(marker.getLatLng(), true); });
                } else {
                    marker.setLatLng(latlng);
                }

                latInput.value = latlng.lat.toFixed(7);
                lngInput.value = latlng.lng.toFixed(7);

                if (withAddress) fillAddress(latlng);
            }

            // Klik peta = pindahkan pin
            map.on('click', function (e) { setPosition(e.latlng, true); });

            if (latInput.value && lngInput.value) {
                // Pulihkan lokasi setelah validasi gagal
                const saved = L.latLng(parseFloat(latInput.value), parseFloat(lngInput.value));
                map.setView(saved, 17);
                setPosition(saved, false);
            } else if (navigator.geolocation) {
                // Coba pakai lokasi perangkat
                navigator.geolocation.getCurrentPosition(function (pos) {
                    const here = L.latLng(pos.coords.latitude, pos.coords.longitude);
                    map.setView(here, 17);
                    setPosition(here, true);
                }, function () { /* ditolak: user pilih manual di peta */ }, {
                    enableHighAccuracy: true,
                    timeout: 10000
                });
            }
        })();
    </script>
@endsection
