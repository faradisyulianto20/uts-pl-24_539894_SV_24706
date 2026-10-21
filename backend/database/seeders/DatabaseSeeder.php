<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin Zaskia',
                'password' => 'password',
                'role' => 'admin',
            ]
        );

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => 'password',
            ]
        );

        if (Post::count() === 0) {
            Post::create([
                'user_id' => $admin->id,
                'title' => 'Selamat Datang di Zaskia Literary Blog',
                'slug' => 'selamat-datang-di-zaskia-literary-blog',
                'content' => "Ruang kecil untuk sastra, opini, dan bacaan sore.\n\nTulisan-tulisan di sini lahir dari catatan pinggir, perjalanan, dan percakapan panjang tentang buku. Semoga menemani kopi Anda.",
                'published_at' => now()->toDateString(),
                'is_popular' => true,
                'views' => 0,
            ]);

            Post::create([
                'user_id' => $admin->id,
                'title' => 'Catatan Membaca: Puisi yang Menolak Bubar',
                'slug' => 'catatan-membaca-puisi-yang-menolak-bubar',
                'content' => "Membaca ulang kumpulan puisi lama selalu terasa seperti pulang ke rumah yang sedikit berubah.\n\nKali ini saya mencatat bagaimana rupanya kata-kata tua itu tetap relevan — bahkan untuk kegalauan yang baru lahir minggu ini.",
                'published_at' => now()->subDays(3)->toDateString(),
                'is_popular' => false,
                'views' => 0,
            ]);
        }
    }
}
