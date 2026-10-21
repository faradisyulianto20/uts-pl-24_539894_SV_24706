<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_without_credentials_returns_422(): void
    {
        $response = $this->postJson('/api/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_with_wrong_password_returns_401(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'salah-password',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Kredensial tidak valid.']);
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'admin@example.com');
    }

    public function test_register_with_valid_data_returns_201_and_token(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Mahasiswa UTS',
            'email' => 'mahasiswa@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'token', 'user' => ['id', 'name', 'email']])
            ->assertJsonPath('user.email', 'mahasiswa@example.com');

        $this->assertDatabaseHas('users', ['email' => 'mahasiswa@example.com']);
    }

    public function test_register_with_invalid_data_returns_422(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => '',
            'email' => 'bukan-email',
            'password' => 'pendek',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertStatus(401);
    }

    public function test_me_with_token_returns_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/me');

        $response->assertStatus(200)
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_logout_deletes_current_token(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('api')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout berhasil.']);

        $this->assertCount(0, $user->tokens);
    }

    public function test_login_with_google_only_account_returns_401(): void
    {
        User::factory()->create([
            'email' => 'google-only@example.com',
            'password' => null,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'google-only@example.com',
            'password' => 'apapun',
        ]);

        $response->assertStatus(401)
            ->assertJson(['message' => 'Kredensial tidak valid.']);
    }

    public function test_google_redirect_returns_302_to_google(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
            'services.google.redirect' => 'http://localhost:8000/api/auth/google/callback',
        ]);

        $response = $this->get('/api/auth/google');

        $response->assertStatus(302);
        $this->assertStringStartsWith(
            'https://accounts.google.com',
            $response->headers->get('Location')
        );
    }

    public function test_google_redirect_without_config_redirects_to_login_with_error(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $response = $this->get('/api/auth/google');

        $response->assertStatus(302);
        $this->assertStringContainsString(
            'error=google_not_configured',
            $response->headers->get('Location')
        );
    }

    public function test_google_callback_failure_redirects_with_error(): void
    {
        Socialite::shouldReceive('driver')->with('google')->andReturn($driver = \Mockery::mock());
        $driver->shouldReceive('user')->andThrow(new \RuntimeException('socialite gagal'));

        $response = $this->get('/api/auth/google/callback');

        $response->assertStatus(302);
        $this->assertStringContainsString(
            'error=google_failed',
            $response->headers->get('Location')
        );
    }

    public function test_google_callback_success_creates_user_and_returns_token(): void
    {
        $profile = (new SocialiteUser)->map([
            'id' => 'google-id-123',
            'name' => 'Google User',
            'email' => 'google.user@example.com',
            'avatar' => 'https://example.com/avatar.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver = \Mockery::mock());
        $driver->shouldReceive('user')->andReturn($profile);

        $response = $this->get('/api/auth/google/callback');

        $response->assertStatus(302);
        $this->assertStringContainsString('/auth/callback?token=', $response->headers->get('Location'));

        $this->assertDatabaseHas('users', [
            'google_id' => 'google-id-123',
            'email' => 'google.user@example.com',
        ]);
    }

    public function test_google_callback_links_existing_email_account_instead_of_failing(): void
    {
        $existing = User::factory()->create([
            'email' => 'sudah-daftar@example.com',
            'password' => 'password',
        ]);

        $profile = (new SocialiteUser)->map([
            'id' => 'google-id-999',
            'name' => 'Dari Google',
            'email' => 'sudah-daftar@example.com',
            'avatar' => 'https://example.com/avatar2.png',
        ]);

        Socialite::shouldReceive('driver')->with('google')->andReturn($driver = \Mockery::mock());
        $driver->shouldReceive('user')->andReturn($profile);

        $response = $this->get('/api/auth/google/callback');

        $response->assertStatus(302);
        $this->assertStringContainsString('/auth/callback?token=', $response->headers->get('Location'));

        $this->assertSame(1, User::count());
        $this->assertSame('google-id-999', $existing->fresh()->google_id);
    }
}
