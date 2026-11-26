<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\ArtworkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ArtworkController extends Controller
{
    public function show(Artwork $art)
    {
        $user = Auth::user();
        $canViewUnapproved = $user && ($user->id === $art->user_id || $user->isAdmin());

        if (! $art->isApproved() && ! $canViewUnapproved) {
            abort(404);
        }

        $art->loadMissing(['user', 'category', 'moderator'])
            ->loadCount(['likedBy', 'savedBy']);

        $isLiked = $user ? $art->likedBy()->where('users.id', $user->id)->exists() : false;
        $isSaved = $user ? $art->savedBy()->where('users.id', $user->id)->exists() : false;
        $reportReasons = ArtworkReport::REASONS;

        return view('artworks.show', compact('art', 'isLiked', 'isSaved', 'reportReasons'));
    }

    public function toggleLike(Request $request, Artwork $art)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if (! $art->isApproved() && $user->id !== $art->user_id && ! $user->isAdmin()) {
            abort(403, 'Karya ini belum disetujui untuk interaksi publik.');
        }

        if ($user->likedArtworks()->wherePivot('artwork_id', $art->id)->exists()) {
            $user->likedArtworks()->detach($art->id);
            $liked = false;
        } else {
            $user->likedArtworks()->attach($art->id);
            $liked = true;
        }

        $likeCount = $art->likedBy()->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'liked' => $liked,
                'like_count' => $likeCount,
            ]);
        }

        return back()->with('success', $liked ? 'Liked' : 'Unliked');
    }

    public function toggleSave(Request $request, Artwork $art)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if (! $art->isApproved() && $user->id !== $art->user_id && ! $user->isAdmin()) {
            abort(403, 'Karya ini belum disetujui untuk interaksi publik.');
        }

        if ($user->savedArtworks()->wherePivot('artwork_id', $art->id)->exists()) {
            $user->savedArtworks()->detach($art->id);
            $saved = false;
        } else {
            $user->savedArtworks()->attach($art->id);
            $saved = true;
        }

        $saveCount = $art->savedBy()->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'saved' => $saved,
                'save_count' => $saveCount,
            ]);
        }

        return back()->with('success', $saved ? 'Saved' : 'Removed from saved');
    }

    public function recordShare(Artwork $art)
    {
        $user = Auth::user();
        if (! $art->isApproved() && (! $user || ($user->id !== $art->user_id && ! $user->isAdmin()))) {
            abort(404);
        }

        // increment share count; sharing can be recorded without auth
        $art->increment('share_count');
        $art->refresh();

        return response()->json(['share_count' => $art->share_count]);
    }
}
