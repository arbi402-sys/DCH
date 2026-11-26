<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Artwork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class UploadController extends Controller
{
    public function storeArtwork(Request $request)
    {
        if (! Schema::hasTable('artworks')) {
            return back()->with('error', 'Artworks table not found. Please run database migrations.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:150',
            'category_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'image' => 'required|image|mimes:jpg,jpeg,png,webp|max:'.Artwork::MAX_UPLOAD_KB,
            'license' => ['required', 'string', Rule::in(array_keys(Artwork::LICENSES))],
            'originality_confirmed' => ['required', 'accepted'],
        ], [
            'image.max' => 'Ukuran file karya maksimal 10 MB (10.240 KB).',
            'image.mimes' => 'Format gambar karya harus JPG, JPEG, PNG, atau WEBP.',
            'license.required' => 'Pilih lisensi hak cipta untuk karya Anda.',
            'license.in' => 'Pilihan lisensi karya tidak valid.',
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
        // Explicitly enforce pending status regardless of request payload
        $artwork->status = Artwork::STATUS_PENDING;
        $artwork->save();

        return back()->with('success', 'Karya berhasil diunggah dan sedang menunggu persetujuan kurator/admin.');
    }

    public function storeArticle(Request $request)
    {
        if (! Schema::hasTable('articles')) {
            return back()->with('error', 'Articles table not found. Please run database migrations.');
        }

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'category_id' => 'nullable|exists:categories,id',
            'body' => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:'.Article::MAX_UPLOAD_KB,
        ], [
            'image.max' => 'Ukuran gambar sampul artikel maksimal 10 MB (10.240 KB).',
            'image.mimes' => 'Format gambar sampul artikel harus JPG, JPEG, PNG, atau WEBP.',
        ]);

        $path = null;
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
        }

        $article = new Article([
            'user_id' => Auth::id(),
            'category_id' => $data['category_id'] ?? null,
            'title' => $data['title'],
            'body' => $data['body'],
            'image_path' => $path,
        ]);
        // Explicitly enforce pending status regardless of request payload
        $article->status = Article::STATUS_PENDING;
        $article->save();

        return back()->with('success', 'Artikel berhasil dikirim dan sedang menunggu persetujuan kurator/admin.');
    }
}
