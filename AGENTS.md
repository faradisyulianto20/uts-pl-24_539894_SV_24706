# 🤖 AGENTS.md (AI Coding Agent Execution Blueprint)

## 1. General Rules & Engineering Standards

1. **Strict Architecture Boundary:** Keep `backend/` and `frontend/` isolated. Root directory only contains orchestration files (`docker-compose.yml`, `.env.example`, `.github/`, docs).
2. **Explicit Image Tagging (No `latest`):** All Dockerfiles must use specific base image versions (e.g., `dunglas/frankenphp:1.4.4-php8.4-alpine`, `node:20.12.2-alpine`, `nginx:1.25.5-alpine`, `mysql:8.0.36`).
3. **Zero Hardcoded Secrets:** Never hardcode passwords, API keys, or APP_KEY in Dockerfiles or source code. Always reference environment variables.
4. **Automated Test Requirement:** Backend must include automated feature tests under `backend/tests/Feature/PostApiTest.php` passing `php artisan test`.
5. **Frontend API Config:** Axios MUST load base URL strictly from `import.meta.env.VITE_API_BASE_URL` or `import.meta.env.VITE_API_BASE`.
6. **Media File Persistence:** Gambar hasil upload disimpan di disk `public` (`storage/app/public/posts`) dan direktori ini di-mount ke named volume Docker (`app_storage`) agar file cover tidak hilang saat container restart.

---

## 2. Project Directory Structure

```text
uts-pl-NIM/
├── .github/
│   └── workflows/
│       └── ci.yml
├── backend/
│   ├── app/
│   │   ├── Http/Controllers/
│   │   │   ├── AuthController.php
│   │   │   ├── PostController.php
│   │   │   └── CommentController.php
│   │   └── Models/
│   │       ├── User.php
│   │       ├── Post.php
│   │       └── Comment.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── tests/
│   │   └── Feature/
│   │       └── PostApiTest.php
│   ├── .dockerignore
│   ├── Dockerfile
│   ├── docker-entrypoint.sh
│   └── composer.json
├── frontend/
│   ├── src/
│   │   ├── assets/
│   │   ├── components/
│   │   │   ├── Navbar.vue
│   │   │   ├── SidebarPopular.vue
│   │   │   └── CommentSection.vue
│   │   ├── services/
│   │   │   └── api.js
│   │   ├── views/
│   │   │   ├── LoginView.vue
│   │   │   ├── RegisterView.vue
│   │   │   ├── AuthCallbackView.vue
│   │   │   ├── BlogView.vue
│   │   │   ├── PostDetailView.vue
│   │   │   └── DashboardView.vue
│   │   ├── router/index.js
│   │   ├── App.vue
│   │   └── main.js
│   ├── nginx.conf
│   ├── .dockerignore
│   ├── Dockerfile
│   └── package.json
├── .env.example
├── docker-compose.yml
├── Postman_Collection.json
├── PRD.md
├── ERD.md
└── AGENTS.md
```

---

## 3. Step-by-Step Execution Sequence for Agent

### Step 1: Backend Setup (Laravel 11/12 + Sanctum + Socialite + Media Upload)

1. Pastikan dependensi terpasang di `backend/`: `laravel/sanctum` dan `laravel/socialite`.
2. Buat migration untuk:
   - `users` (`google_id`, `avatar`, `role`).
   - `posts` (`user_id`, `title`, `slug`, `cover_image`, `content`, `published_at`, `is_popular`, `views`).
   - `comments` (`post_id`, `user_id`, `body`).
3. Konfigurasi Sanctum & Socialite di `config/services.php`.
4. Storage Configuration:
   - Backend menggunakan disk `public` untuk file storage (`storage/app/public/posts`).
   - Eksekusi `php artisan storage:link` saat startup container agar berkas dapat diakses melalui URL publik `/storage/...`.
   - Di Model `Post` atau API Resource, sediakan atribut mutator/appends `cover_image_url` yang menghasilkan full URL gambar (`asset('storage/' . $this->cover_image)`).
5. Implementasikan `PostController`:
   - Pada `POST /api/posts`: cek `$request->hasFile('cover_image')`. Jika ada, simpan berkas dengan `$path = $request->file('cover_image')->store('posts', 'public');` dan simpan `$path` ke basis data.
   - Pada `POST /api/posts/{id}` (dengan `_method=PUT`): periksa jika ada cover baru, hapus gambar lama dari disk, lalu simpan cover baru.
6. Buat feature test di `backend/tests/Feature/PostApiTest.php` dengan skenario:
   - Test 1: Request `POST /api/posts` tanpa token mengembalikan status `401 Unauthorized`.
   - Test 2: Request `POST /api/posts` dengan token Admin mengembalikan status `201 Created` dan data tersimpan di DB.
7. Buat file `Postman_Collection.json` di root repository yang berisi seluruh endpoint.

### Step 2: Frontend Setup (Vue 3 + Axios + Vue Router + FormData)

1. Setup router di `frontend/src/router/index.js`.
2. Setup Axios service di `frontend/src/services/api.js` dengan request interceptor untuk menyisipkan Bearer token dari `localStorage`.
3. Form Submission Upload di `DashboardView.vue`:
   - Gunakan `FormData` untuk mengirim request create/update:
   ```javascript
   const formData = new FormData();
   formData.append('title', title.value);
   formData.append('content', content.value);
   formData.append('published_at', publishedAt.value);
   formData.append('is_popular', isPopular.value ? 1 : 0);
   if (coverImageFile.value) {
       formData.append('cover_image', coverImageFile.value);
   }

   await api.post('/posts', formData, {
       headers: { 'Content-Type': 'multipart/form-data' }
   });
   ```
4. Buat views:
   - `LoginView.vue` dan `RegisterView.vue`.
   - `AuthCallbackView.vue` untuk callback OAuth Google.
   - `BlogView.vue` (tampilan blog sastra/opini publik dengan cover image artikel).
   - `PostDetailView.vue` (detail artikel + cover image + komentar).
   - `DashboardView.vue` (admin CRUD tabel artikel, preview gambar cover, tambah, edit, hapus).

### Step 3: Containerization & Compose (dengan Persistence Volume Media)

1. **`backend/Dockerfile`**: Menggunakan FrankenPHP versi spesifik (PHP 8.4) untuk nilai tambahan +10, menyalin composer files terlebih dahulu untuk caching, dan menjalankan migrasi database serta `storage:link` saat runtime melalui `docker-entrypoint.sh`.
2. **`frontend/Dockerfile`**: Multi-stage build (Node 20 -> Nginx 1.25 alpine) dengan reverse proxy `/api/` ke backend.
3. **`docker-compose.yml`**:
   - Menghubungkan database MySQL, backend FrankenPHP, dan frontend Nginx menggunakan nama service.
   - **Named Volume `app_storage`**: Memetakan `/app/storage/app/public` ke volume persisten `app_storage` agar file upload cover blog tidak hilang saat container di-restart.

### Step 4: GitHub Actions CI/CD Pipeline

Pipeline di `.github/workflows/ci.yml` menjalankan:
1. `backend-test`: Menjalankan `composer install`, `key:generate`, dan `php artisan test` dengan PHP 8.4 dan database sqlite in-memory.
2. `frontend-build`: Menjalankan `npm ci` dan `npm run build`.
3. `deploy`: Hanya berjalan saat push ke `main`, mem-build image Docker dan mengunggahnya ke GitHub Container Registry (`ghcr.io`) dengan tag commit SHA.

---

## 4. Final Agent Verification Checklists

Sebelum menyerahkan hasil pengerjaan, Agent wajib memverifikasi poin berikut:

1. Jalankan `php artisan test` di folder `backend/` -> Harus **PASS** (100% green).
2. Jalankan `docker compose up -d --build` di root -> Seluruh 3 container (`db`, `backend`, `frontend`) harus berstatus **Up/Running**.
3. Buka browser di `http://localhost:8080` -> Halaman blog Vue muncul dan dapat berkomunikasi ke backend Laravel.
4. Lakukan registrasi user baru & login -> Token Sanctum tersimpan di `localStorage`.
5. Uji coba upload cover image pada postingan blog -> Gambar tampil sempurna di frontend.
6. Lakukan uji coba `docker compose down` lalu `docker compose up -d` -> Data di database dan file gambar yang diupload tidak hilang (Named volume `db_data` dan `app_storage` terverifikasi).
