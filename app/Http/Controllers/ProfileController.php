<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show($identifier, Request $request)
    {
        // allow either numeric id or username as identifier
        if (is_numeric($identifier)) {
            $user = User::find($identifier);
            if (! $user) {
                \Log::warning('Profile lookup failed (numeric id)', ['identifier' => $identifier, 'auth_id' => Auth::id()]);
                $authUser = Auth::user();

                return response()->view('errors.profile_not_found', ['identifier' => $identifier, 'authUser' => $authUser], 404);
            }
        } else {
            // tolerant username lookup to avoid 404s caused by encoding, dashes/spaces, or case differences
            $decoded = rawurldecode($identifier);
            $alt = str_replace('-', ' ', $decoded);
            $lower = mb_strtolower($decoded);

            $user = User::where('username', $decoded)
                ->orWhere('username', $alt)
                ->orWhereRaw('lower(username) = ?', [$lower])
                ->first();

            if (! $user) {
                $user = User::where('username', $identifier)->first();
            }

            if (! $user) {
                \Log::warning('Profile lookup failed (username)', ['identifier' => $identifier, 'auth_id' => Auth::id()]);
                $authUser = Auth::user();

                return response()->view('errors.profile_not_found', ['identifier' => $identifier, 'authUser' => $authUser], 404);
            }
        }

        $category = $request->get('category');
        $authUser = Auth::user();
        $isOwnerOrAdmin = $authUser && ($authUser->id === $user->id || $authUser->isAdmin());

        $authLikedIds = [];
        $authSavedIds = [];

        if (Schema::hasTable('artworks')) {
            $artworksQuery = $user->artworks()
                ->with(['category', 'user'])
                ->withCount(['likedBy', 'savedBy'])
                ->when(! $isOwnerOrAdmin, fn ($q) => $q->approved())
                ->when($category, function ($q, $category) {
                    $q->whereHas('category', fn ($q2) => $q2->where('slug', $category));
                });

            $artworks = $artworksQuery->latest()->paginate(12);

            // Liked and saved lists show approved artworks (so unapproved content of other users never leaks)
            $liked = $user->likedArtworks()
                ->approved()
                ->with(['category', 'user'])
                ->withCount(['likedBy', 'savedBy'])
                ->latest()
                ->paginate(12, ['*'], 'liked_page');

            $saved = $user->savedArtworks()
                ->approved()
                ->with(['category', 'user'])
                ->withCount(['likedBy', 'savedBy'])
                ->latest()
                ->paginate(12, ['*'], 'saved_page');

            if ($authUser && Schema::hasTable('artwork_likes')) {
                $authLikedIds = $authUser->likedArtworks()->pluck('artworks.id')->all();
            }
            if ($authUser && Schema::hasTable('artwork_saves')) {
                $authSavedIds = $authUser->savedArtworks()->pluck('artworks.id')->all();
            }
        } else {
            $artworks = new LengthAwarePaginator([], 0, 12, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
            $liked = new LengthAwarePaginator([], 0, 12, 1, ['path' => $request->url(), 'query' => $request->query()]);
            $saved = new LengthAwarePaginator([], 0, 12, 1, ['path' => $request->url(), 'query' => $request->query()]);
        }

        $categories = Schema::hasTable('categories') ? Category::all() : collect();

        if (Schema::hasTable('articles')) {
            $articles = $user->articles()
                ->when(! $isOwnerOrAdmin, fn ($q) => $q->approved())
                ->latest()
                ->paginate(6);
        } else {
            $articles = new LengthAwarePaginator([], 0, 6, 1, ['path' => $request->url(), 'query' => $request->query()]);
        }

        if (Schema::hasTable('follows')) {
            $followersCount = $user->followers()->count();
            $followingCount = $user->following()->count();
            $isFollowing = $authUser ? $authUser->following()->where('followed_id', $user->id)->exists() : false;
        } else {
            $followersCount = 0;
            $followingCount = 0;
            $isFollowing = false;
        }

        return view('profile.show', compact(
            'user',
            'artworks',
            'categories',
            'isFollowing',
            'followersCount',
            'followingCount',
            'liked',
            'saved',
            'articles',
            'isOwnerOrAdmin',
            'authLikedIds',
            'authSavedIds'
        ));
    }

    public function edit()
    {
        $user = Auth::user();
        $categories = Schema::hasTable('categories') ? Category::all() : collect();

        return view('profile.edit', compact('user', 'categories'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        \Log::info('Profile update request', ['user_id' => $user->id]);

        $data = $request->validate([
            'first_name' => 'nullable|string|max:50',
            'last_name' => 'nullable|string|max:50',
            'username' => 'sometimes|required|string|max:50|unique:users,username,'.$user->id,
            'bio' => 'nullable|string|max:500',
            'headline' => 'nullable|string|max:150',
            'institution' => 'nullable|string|max:150',
            'location' => 'nullable|string|max:150',
            'city' => 'nullable|string|max:100',
            'website' => 'nullable|url|max:255',
            'ig' => 'nullable|url|max:255',
            'avatar' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'cover_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
        ], [
            'avatar.max' => 'Ukuran foto profil (avatar) maksimal 2 MB.',
            'cover_image.max' => 'Ukuran gambar sampul (cover) maksimal 4 MB.',
            'website.url' => 'Format URL website tidak valid (gunakan awalan https://).',
            'ig.url' => 'Format URL Instagram tidak valid (gunakan awalan https://).',
        ]);

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('covers', 'public');
        }

        // derive display name from first/last name when provided
        if (! empty($data['first_name']) || ! empty($data['last_name'])) {
            $data['name'] = trim(($data['first_name'] ?? $user->first_name).' '.($data['last_name'] ?? $user->last_name));
        }

        try {
            $user->update($data);

            return back()->with('success', 'Profile berhasil diperbarui.');
        } catch (\Exception $e) {
            \Log::error('Profile update error: '.$e->getMessage(), ['user_id' => $user->id]);

            return back()->withErrors(['error' => 'Gagal memperbarui profile: '.$e->getMessage()]);
        }
    }

    public function storeArtwork(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'description' => 'nullable|string|max:1000',
            'category_id' => 'nullable|exists:categories,id',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:'.Artwork::MAX_UPLOAD_KB,
            'license' => ['required', 'string', Rule::in(array_keys(Artwork::LICENSES))],
            'originality_confirmed' => ['required', 'accepted'],
        ], [
            'image.max' => 'Ukuran file karya maksimal 10 MB (10.240 KB).',
            'image.mimes' => 'Format gambar karya harus JPG, JPEG, PNG, atau WEBP.',
            'license.required' => 'Pilih lisensi hak cipta untuk karya Anda.',
            'originality_confirmed.required' => 'Anda wajib menyetujui pernyataan orisinalitas dan hak unggah karya.',
            'originality_confirmed.accepted' => 'Anda wajib menyetujui pernyataan orisinalitas dan hak unggah karya.',
        ]);

        $path = $request->file('image')->store('artworks', 'public');

        $artwork = new Artwork([
            'user_id' => Auth::id(),
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'image_path' => $path,
            'license' => $data['license'],
            'originality_confirmed' => true,
            'copyright_confirmed_at' => now(),
        ]);
        $artwork->status = Artwork::STATUS_PENDING;
        $artwork->save();

        return back()->with('success', 'Karya berhasil diunggah dan sedang menunggu persetujuan kurator/admin.');
    }

    public function destroyArtwork(Request $request, Artwork $art)
    {
        $user = Auth::user();
        if (! $user || ($user->id !== $art->user_id && ! $user->isAdmin())) {
            abort(403);
        }

        if ($art->image_path && Storage::disk('public')->exists($art->image_path)) {
            Storage::disk('public')->delete($art->image_path);
        }

        $art->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Artwork deleted.');
    }

    public function follow($username)
    {
        $target = User::where('username', $username)->firstOrFail();
        $me = Auth::user();

        if ($me->id === $target->id) {
            return back();
        }

        $me->following()->syncWithoutDetaching([$target->id]);

        return back()->with('success', 'You are now following '.$target->username);
    }

    public function unfollow($username)
    {
        $target = User::where('username', $username)->firstOrFail();
        $me = Auth::user();
        $me->following()->detach($target->id);

        return back()->with('success', 'Unfollowed '.$target->username);
    }
}
