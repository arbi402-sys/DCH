<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadSidebarTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_see_upload_actions()
    {
        \Illuminate\Support\Facades\Auth::logout();

        $res = $this->get('/');
        $res->assertDontSee('Upload Karya');
        $res->assertDontSee('Upload Artikel');
    }

    public function test_authenticated_user_can_upload_artwork_via_sidebar_and_it_appears_after_approval()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'Sidebar Art',
                'description' => 'desc',
                'image' => UploadedFile::fake()->create('art.jpg', 800, 'image/jpeg'),
                'license' => 'cc_by_nc',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHas('success');

        $artwork = Artwork::where('title', 'Sidebar Art')->firstOrFail();
        $this->assertEquals(Artwork::STATUS_PENDING, $artwork->status);

        // Should NOT appear publicly before moderation approval
        $this->get('/')->assertDontSee('data-title="Sidebar Art"', false);

        // Approve as admin
        $this->actingAs($admin)
            ->post(route('admin.moderation.artworks.approve', $artwork->id))
            ->assertSessionHas('success');

        // Explore page should now include thumbnail with data attributes for modal
        $res = $this->actingAs($user)->get('/');
        $res->assertSee('data-title="Sidebar Art"', false);
        $res->assertSee('data-artwork-desc="desc"', false);
        $res->assertSee('id="modalAvatar"', false);
        $res->assertSee('id="modalCreatorName"', false);
        $res->assertSee('id="modalCreatorUsername"', false);
        $res->assertSee('Karya');
        $res->assertSee('Artikel');
    }

    public function test_uploaded_article_appears_in_community_listing_after_approval()
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)
            ->post(route('upload.article'), [
                'title' => 'Sidebar Article',
                'body' => str_repeat('content ', 40),
                'image' => UploadedFile::fake()->create('cover.jpg', 900, 'image/jpeg'),
            ])
            ->assertSessionHas('success');

        $article = Article::where('title', 'Sidebar Article')->firstOrFail();
        $this->assertEquals(Article::STATUS_PENDING, $article->status);

        // Hidden publicly while pending
        $this->get('/community')->assertDontSee('Sidebar Article');

        // Approve as admin
        $this->actingAs($admin)
            ->post(route('admin.moderation.articles.approve', $article->id))
            ->assertSessionHas('success');

        $res = $this->get('/community');
        $res->assertSee('Sidebar Article');
        $res->assertSee('By');
    }

    public function test_authenticated_user_can_upload_article_via_sidebar()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('upload.article'), [
                'title' => 'Sidebar Article',
                'body' => str_repeat('content ', 40),
                'image' => UploadedFile::fake()->create('cover.jpg', 900, 'image/jpeg'),
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('articles', [
            'title' => 'Sidebar Article',
            'user_id' => $user->id,
            'status' => Article::STATUS_PENDING,
        ]);
    }

    public function test_article_post_fails_when_articles_table_missing()
    {
        Schema::dropIfExists('articles');

        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('upload.article'), [
                'title' => 'Sidebar Article',
                'body' => str_repeat('content ', 40),
                'image' => UploadedFile::fake()->create('cover.jpg', 900, 'image/jpeg'),
            ])
            ->assertSessionHas('error');
    }
}
