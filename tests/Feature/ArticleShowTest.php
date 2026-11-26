<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_community_lists_article_with_profile_link()
    {
        Storage::fake('public');
        $user = User::factory()->create(['username' => 'mahasiswa1', 'role' => 'mahasiswa']);

        $article = Article::create([
            'user_id' => $user->id,
            'title' => 'Test Article',
            'body' => '<p>This is the <strong>body</strong> of the article.</p>',
        ]);
        $article->status = Article::STATUS_APPROVED;
        $article->save();

        $res = $this->get('/community');
        $res->assertSee('Test Article');
        $res->assertSee(route('profile.show', $user->username));
        $res->assertSee(route('articles.show', $article->id));
    }

    public function test_article_show_displays_content_and_profile_link()
    {
        $user = User::factory()->create(['username' => 'dosen1', 'role' => 'dosen']);

        $article = Article::create([
            'user_id' => $user->id,
            'title' => 'Dosen Article',
            'body' => '<p>Full article body</p>',
        ]);
        $article->status = Article::STATUS_APPROVED;
        $article->save();

        $res = $this->get(route('articles.show', $article->id));
        $res->assertStatus(200);
        $res->assertSee('Dosen Article');
        $res->assertSee('Full article body');
        $res->assertSee(route('profile.show', $user->username));
    }
}
