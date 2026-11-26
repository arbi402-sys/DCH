<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ArticleController extends Controller
{
    public function show(Article $article)
    {
        $user = Auth::user();
        $canViewUnapproved = $user && ($user->id === $article->user_id || $user->isAdmin());

        if (! $article->isApproved() && ! $canViewUnapproved) {
            abort(404);
        }

        $article->loadMissing('user', 'category', 'moderator');

        return view('articles.show', compact('article'));
    }

    public function destroy(Request $request, Article $article)
    {
        $user = Auth::user();
        if (! $user || ($user->id !== $article->user_id && ! $user->isAdmin())) {
            abort(403);
        }

        if ($article->image_path) {
            try {
                Storage::disk('public')->delete($article->image_path);
            } catch (\Exception $e) {
                // ignore cleanup errors
            }
        }

        $article->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('status', 'Artikel berhasil dihapus.');
    }
}
