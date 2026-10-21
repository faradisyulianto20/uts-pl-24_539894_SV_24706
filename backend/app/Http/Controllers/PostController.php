<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Post::with('user:id,name,avatar')->latest();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('popular')) {
            $query->where('is_popular', true);
        }

        return response()->json([
            'posts' => $query->get(),
        ]);
    }

    public function show(Request $request, Post $post): JsonResponse
    {
        $post->increment('views');
        $post->load([
            'user:id,name,avatar',
            'comments' => fn ($q) => $q->latest(),
            'comments.user:id,name,avatar',
        ]);

        return response()->json([
            'post' => $post,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'published_at' => ['required', 'date'],
            'is_popular' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data = [
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'slug' => $this->generateUniqueSlug($validated['title']),
            'content' => $validated['content'],
            'published_at' => $validated['published_at'],
            'is_popular' => $validated['is_popular'] ?? false,
        ];

        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('posts', 'public');
        }

        $post = Post::create($data);

        return response()->json([
            'message' => 'Post berhasil dibuat.',
            'post' => $post->load('user:id,name,avatar'),
        ], 201);
    }

    public function update(Request $request, Post $post): JsonResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['sometimes', 'required', 'string'],
            'published_at' => ['sometimes', 'required', 'date'],
            'is_popular' => ['nullable', 'boolean'],
            'cover_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if (array_key_exists('title', $validated) && $validated['title'] !== $post->title) {
            $validated['slug'] = $this->generateUniqueSlug($validated['title'], $post->id);
        }

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image) {
                Storage::disk('public')->delete($post->cover_image);
            }
            $validated['cover_image'] = $request->file('cover_image')->store('posts', 'public');
        }

        $post->update($validated);

        return response()->json([
            'message' => 'Post berhasil diperbarui.',
            'post' => $post->load('user:id,name,avatar'),
        ]);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($post->cover_image) {
            Storage::disk('public')->delete($post->cover_image);
        }

        $post->delete();

        return response()->json([
            'message' => 'Post berhasil dihapus.',
        ]);
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Hanya admin yang diizinkan.');
    }

    private function generateUniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $suffix = 1;

        while (
            Post::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original.'-'.$suffix++;
        }

        return $slug !== '' ? $slug : Str::random(10);
    }
}
