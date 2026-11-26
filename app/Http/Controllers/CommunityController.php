<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;

class CommunityController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->get('category');

        if (Schema::hasTable('articles')) {
            $articles = Article::approved()
                ->with('user', 'category')
                ->when($category, fn ($q) => $q->whereHas('category', fn ($q2) => $q2->where('slug', $category)))
                ->latest()
                ->paginate(10);
        } else {
            $articles = new LengthAwarePaginator([], 0, 10, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);
        }

        $categories = Schema::hasTable('categories') ? Category::all() : collect();

        return view('community', compact('articles', 'categories'));
    }
}
