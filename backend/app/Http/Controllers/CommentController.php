<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Post $post): JsonResponse
    {
        $comments = $post->comments()
            ->with('user:id,name,avatar')
            ->latest()
            ->get();

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function store(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = $post->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan.',
            'comment' => $comment->load('user:id,name,avatar'),
        ], 201);
    }

    public function adminIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Hanya admin yang diizinkan.');

        $comments = Comment::with('post:id,title,slug', 'user:id,name,avatar')
            ->latest()
            ->get();

        return response()->json([
            'comments' => $comments,
        ]);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403, 'Hanya admin yang diizinkan.');

        $comment->delete();

        return response()->json([
            'message' => 'Komentar berhasil dihapus.',
        ]);
    }
}
