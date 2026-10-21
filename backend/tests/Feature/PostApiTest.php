<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PostApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_posts_index_requires_authentication(): void
    {
        $this->getJson('/api/posts')->assertStatus(401);
    }

    public function test_posts_index_without_token_returns_401_even_without_json_accept(): void
    {
        $this->get('/api/posts')->assertStatus(401);
    }

    public function test_posts_index_returns_posts_with_user(): void
    {
        Post::factory()->count(2)->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/posts')
            ->assertStatus(200)
            ->assertJsonCount(2, 'posts')
            ->assertJsonStructure(['posts' => [['id', 'title', 'slug', 'cover_image_url', 'user' => ['id', 'name']]]]);
    }

    public function test_posts_index_filters_popular(): void
    {
        Post::factory()->popular()->create();
        Post::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/posts?popular=1')
            ->assertStatus(200)
            ->assertJsonCount(1, 'posts')
            ->assertJsonPath('posts.0.is_popular', true);
    }

    public function test_show_post_increments_views_and_loads_comments(): void
    {
        $post = Post::factory()->create(['views' => 3]);
        Comment::factory()->create(['post_id' => $post->id]);
        Sanctum::actingAs(User::factory()->create());

        $this->getJson("/api/posts/{$post->id}")
            ->assertStatus(200)
            ->assertJsonPath('post.views', 4)
            ->assertJsonStructure(['post' => ['id', 'comments' => [['id', 'body']]]]);

        $this->assertSame(4, $post->fresh()->views);
    }

    public function test_store_post_requires_authentication(): void
    {
        $this->postJson('/api/posts', [])->assertStatus(401);
    }

    public function test_store_post_as_admin_returns_201_and_persists(): void
    {
        Sanctum::actingAs($this->admin());

        $payload = [
            'title' => 'Resensi Buku Hujan',
            'content' => 'Isi resensi yang panjang dan mendalam.',
            'published_at' => '2026-10-21',
            'is_popular' => 1,
        ];

        $this->postJson('/api/posts', $payload)
            ->assertStatus(201)
            ->assertJsonStructure(['message', 'post' => ['id', 'slug', 'cover_image_url', 'user' => ['id']]])
            ->assertJsonPath('post.title', 'Resensi Buku Hujan')
            ->assertJsonPath('post.slug', 'resensi-buku-hujan')
            ->assertJsonPath('post.is_popular', true);

        $this->assertDatabaseHas('posts', [
            'title' => 'Resensi Buku Hujan',
            'slug' => 'resensi-buku-hujan',
        ]);
    }

    public function test_store_post_as_regular_user_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/posts', [
            'title' => 'Tulisannya User',
            'content' => 'Harusnya ditolak.',
            'published_at' => '2026-10-21',
        ])->assertStatus(403);
    }

    public function test_store_post_with_invalid_data_returns_422(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/posts', ['content' => 'Tanpa judul'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'published_at']);
    }

    public function test_store_post_with_cover_image_uploads_to_public_disk(): void
    {
        Sanctum::actingAs($this->admin());

        $file = UploadedFile::fake()->image('cover.jpg');

        $response = $this->postJson('/api/posts', [
            'title' => 'Post Dengan Cover',
            'content' => 'Konten.',
            'published_at' => '2026-10-21',
            'cover_image' => $file,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('post.cover_image', fn ($path) => str_starts_with((string) $path, 'posts/'))
            ->assertJsonPath('post.cover_image_url', fn ($url) => str_starts_with((string) $url, '/storage/posts/'));

        Storage::disk('public')->assertExists($response->json('post.cover_image'));
    }

    public function test_update_post_replaces_cover_and_deletes_old_file(): void
    {
        $post = Post::factory()->create([
            'cover_image' => 'posts/old-cover.jpg',
        ]);
        Storage::disk('public')->put('posts/old-cover.jpg', 'lama');

        Sanctum::actingAs($this->admin());

        $newFile = UploadedFile::fake()->image('baru.jpg');

        $this->postJson("/api/posts/{$post->id}?_method=PUT", [
            'title' => $post->title,
            'content' => 'Konten diperbarui.',
            'published_at' => '2026-10-22',
            'cover_image' => $newFile,
        ])->assertStatus(200);

        Storage::disk('public')->assertMissing('posts/old-cover.jpg');
        $this->assertNotSame('posts/old-cover.jpg', $post->fresh()->cover_image);
        Storage::disk('public')->assertExists($post->fresh()->cover_image);
    }

    public function test_update_post_regenerates_slug_when_title_changes(): void
    {
        $post = Post::factory()->create([
            'title' => 'Judul Lama',
            'slug' => 'judul-lama',
        ]);

        Sanctum::actingAs($this->admin());

        $this->putJson("/api/posts/{$post->id}", [
            'title' => 'Judul Baru Total',
            'content' => $post->content,
            'published_at' => '2026-10-22',
        ])->assertStatus(200)
            ->assertJsonPath('post.slug', 'judul-baru-total');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => 'judul-baru-total']);
    }

    public function test_destroy_post_removes_record_and_cover_file(): void
    {
        $post = Post::factory()->create([
            'cover_image' => 'posts/cover-hapus.jpg',
        ]);
        Storage::disk('public')->put('posts/cover-hapus.jpg', 'isi');

        Sanctum::actingAs($this->admin());

        $this->deleteJson("/api/posts/{$post->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        Storage::disk('public')->assertMissing('posts/cover-hapus.jpg');
    }

    public function test_destroy_post_as_regular_user_is_forbidden(): void
    {
        $post = Post::factory()->create();
        Sanctum::actingAs(User::factory()->create());

        $this->deleteJson("/api/posts/{$post->id}")->assertStatus(403);
        $this->assertDatabaseHas('posts', ['id' => $post->id]);
    }

    public function test_store_comment_as_authenticated_user_returns_201(): void
    {
        $post = Post::factory()->create();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson("/api/posts/{$post->id}/comments", ['body' => 'Tulisannya menyentuh sekali.'])
            ->assertStatus(201)
            ->assertJsonStructure(['comment' => ['id', 'body', 'user' => ['id']]]);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'body' => 'Tulisannya menyentuh sekali.',
        ]);
    }

    public function test_admin_comments_endpoint_forbidden_for_regular_user(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/admin/comments')->assertStatus(403);
    }

    public function test_admin_comments_endpoint_returns_all_comments_for_admin(): void
    {
        Comment::factory()->count(2)->create();
        Sanctum::actingAs($this->admin());

        $this->getJson('/api/admin/comments')
            ->assertStatus(200)
            ->assertJsonCount(2, 'comments')
            ->assertJsonStructure(['comments' => [['id', 'body', 'post' => ['id', 'title'], 'user' => ['id', 'name']]]]);
    }

    public function test_admin_can_delete_comment(): void
    {
        $comment = Comment::factory()->create();
        Sanctum::actingAs($this->admin());

        $this->deleteJson("/api/admin/comments/{$comment->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_update_me_updates_profile(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->putJson('/api/me', [
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ])->assertStatus(200)
            ->assertJsonPath('user.name', 'Nama Baru')
            ->assertJsonPath('user.email', 'baru@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ]);
    }
}
