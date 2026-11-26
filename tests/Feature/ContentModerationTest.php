<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Artwork;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContentModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_artwork_and_article_are_pending_and_cannot_bypass_via_payload()
    {
        Storage::fake('public');
        $user = User::factory()->create(['role' => 'mahasiswa']);
        $category = Category::create(['name' => 'Graphic Design', 'slug' => 'graphic']);

        // Attempt to inject status=approved in artwork payload
        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'Pending Artwork Secret',
                'description' => 'Should stay pending',
                'category_id' => $category->id,
                'image' => UploadedFile::fake()->create('pending.jpg', 500, 'image/jpeg'),
                'license' => 'all_rights_reserved',
                'originality_confirmed' => '1',
                'status' => 'approved',
                'moderated_by' => $user->id,
            ])
            ->assertSessionHas('success');

        // Attempt to inject status=approved in article payload
        $this->actingAs($user)
            ->post(route('upload.article'), [
                'title' => 'Pending Article Secret',
                'body' => 'Body of pending article that must not appear publicly.',
                'status' => 'approved',
            ])
            ->assertSessionHas('success');

        $artwork = Artwork::where('title', 'Pending Artwork Secret')->firstOrFail();
        $article = Article::where('title', 'Pending Article Secret')->firstOrFail();

        $this->assertEquals(Artwork::STATUS_PENDING, $artwork->status);
        $this->assertNull($artwork->moderated_by);
        $this->assertEquals(Article::STATUS_PENDING, $article->status);
        $this->assertNull($article->moderated_by);

        // Must NOT leak to any public endpoints or other users (verify as guest first)
        \Illuminate\Support\Facades\Auth::logout();
        $otherUser = User::factory()->create(['role' => 'client']);

        $this->get('/')->assertOk()->assertDontSee('Pending Artwork Secret');
        $this->get('/explore')->assertOk()->assertDontSee('Pending Artwork Secret');
        $this->get('/dashboard')->assertOk()->assertDontSee('Pending Artwork Secret');
        $this->getJson('/api/explore/load-more?page=1')->assertOk()->assertDontSee('Pending Artwork Secret');
        $this->get('/search?q=Pending')->assertOk()->assertDontSee('Pending Artwork Secret');
        $this->get('/category/graphic')->assertOk()->assertDontSee('Pending Artwork Secret');
        $this->get('/community')->assertOk()->assertDontSee('Pending Article Secret');

        // Direct detail pages return 404 for guests and non-owners
        $this->get(route('artworks.show', $artwork->id))->assertStatus(404);
        $this->get(route('articles.show', $article->id))->assertStatus(404);
        $this->actingAs($otherUser)->get(route('artworks.show', $artwork->id))->assertStatus(404);
        $this->actingAs($otherUser)->get(route('articles.show', $article->id))->assertStatus(404);

        // Other user visiting author's profile does NOT see pending artwork/article
        $this->actingAs($otherUser)
            ->get(route('profile.show', $user->username))
            ->assertOk()
            ->assertDontSee('Pending Artwork Secret')
            ->assertDontSee('Pending Article Secret');

        // Owner CAN see their own pending artwork/article and status badge
        $this->actingAs($user)
            ->get(route('profile.show', $user->username))
            ->assertOk()
            ->assertSee('Pending Artwork Secret')
            ->assertSee('Menunggu Kurasi');

        $this->actingAs($user)
            ->get(route('artworks.show', $artwork->id))
            ->assertOk()
            ->assertSee('PENDING');

        $this->actingAs($user)
            ->get(route('articles.show', $article->id))
            ->assertOk()
            ->assertSee('PENDING');
    }

    public function test_non_admin_users_receive_403_on_moderation_endpoints()
    {
        $owner = User::factory()->create(['role' => 'mahasiswa']);
        $artwork = Artwork::create([
            'user_id' => $owner->id,
            'title' => 'Needs Review',
            'image_path' => 'artworks/review.jpg',
        ]);
        $article = Article::create([
            'user_id' => $owner->id,
            'title' => 'Article Needs Review',
            'body' => 'Article body content.',
        ]);

        foreach (['mahasiswa', 'dosen', 'client'] as $role) {
            $regular = User::factory()->create(['role' => $role]);

            $this->actingAs($regular)->get(route('admin.moderation.index'))->assertStatus(403);
            $this->actingAs($regular)->post(route('admin.moderation.artworks.approve', $artwork->id))->assertStatus(403);
            $this->actingAs($regular)->post(route('admin.moderation.artworks.reject', $artwork->id), ['moderation_note' => 'Unauthorized reject'])->assertStatus(403);
            $this->actingAs($regular)->post(route('admin.moderation.articles.approve', $article->id))->assertStatus(403);
            $this->actingAs($regular)->post(route('admin.moderation.articles.reject', $article->id), ['moderation_note' => 'Unauthorized reject'])->assertStatus(403);
        }

        // Guest redirected to login
        \Illuminate\Support\Facades\Auth::logout();
        $this->get(route('admin.moderation.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_approve_and_reject_artworks_and_articles_with_notes()
    {
        $owner = User::factory()->create(['role' => 'mahasiswa']);
        $admin = User::factory()->create(['role' => 'admin']);

        $artApprove = Artwork::create([
            'user_id' => $owner->id,
            'title' => 'Approved Karya',
            'image_path' => 'artworks/ok.jpg',
        ]);
        $artReject = Artwork::create([
            'user_id' => $owner->id,
            'title' => 'Rejected Karya',
            'image_path' => 'artworks/bad.jpg',
        ]);
        $articleApprove = Article::create([
            'user_id' => $owner->id,
            'title' => 'Approved Artikel',
            'body' => 'Konten artikel yang valid dan layak tayang.',
        ]);
        $articleReject = Article::create([
            'user_id' => $owner->id,
            'title' => 'Rejected Artikel',
            'body' => 'Konten artikel yang ditolak.',
        ]);

        // Admin panel accessible
        $this->actingAs($admin)
            ->get(route('admin.moderation.index'))
            ->assertOk()
            ->assertSee('Approved Karya')
            ->assertSee('Approved Artikel');

        // Reject requires moderation_note
        $this->actingAs($admin)
            ->post(route('admin.moderation.artworks.reject', $artReject->id), ['moderation_note' => ''])
            ->assertSessionHasErrors(['moderation_note']);

        // Approve & reject artworks
        $this->actingAs($admin)
            ->post(route('admin.moderation.artworks.approve', $artApprove->id))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.moderation.artworks.reject', $artReject->id), [
                'moderation_note' => 'Resolusi gambar buram dan sumber referensi tidak dicantumkan.',
            ])
            ->assertSessionHas('success');

        // Approve & reject articles
        $this->actingAs($admin)
            ->post(route('admin.moderation.articles.approve', $articleApprove->id))
            ->assertSessionHas('success');

        $this->actingAs($admin)
            ->post(route('admin.moderation.articles.reject', $articleReject->id), [
                'moderation_note' => 'Artikel belum memenuhi kaidah penulisan komunitas.',
            ])
            ->assertSessionHas('success');

        // Approved content is now publicly visible; rejected remains hidden from public (verify as guest)
        \Illuminate\Support\Facades\Auth::logout();
        $this->get('/explore')->assertOk()->assertSee('Approved Karya')->assertDontSee('Rejected Karya');
        $this->get('/search?q=Approved')->assertOk()->assertSee('Approved Karya');
        $this->get('/community')->assertOk()->assertSee('Approved Artikel')->assertDontSee('Rejected Artikel');
        $this->get(route('artworks.show', $artApprove->id))->assertOk();
        $this->get(route('artworks.show', $artReject->id))->assertStatus(404);
        $this->get(route('articles.show', $articleReject->id))->assertStatus(404);

        // Owner sees rejection reason
        $this->actingAs($owner)
            ->get(route('artworks.show', $artReject->id))
            ->assertOk()
            ->assertSee('Resolusi gambar buram dan sumber referensi tidak dicantumkan.');

        $this->actingAs($owner)
            ->get(route('articles.show', $articleReject->id))
            ->assertOk()
            ->assertSee('Artikel belum memenuhi kaidah penulisan komunitas.');
    }

    public function test_legacy_artworks_and_articles_migration_marks_existing_records_as_approved()
    {
        $user = User::factory()->create();

        // Rollback the 3 latest moderation/report migrations to simulate pre-moderation database state
        Artisan::call('migrate:rollback', ['--step' => 3]);

        $now = now()->toDateTimeString();
        $legacyArtworkId = DB::table('artworks')->insertGetId([
            'user_id' => $user->id,
            'title' => 'Legacy Artwork',
            'description' => 'Uploaded before moderation feature',
            'image_path' => 'artworks/legacy.jpg',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $legacyArticleId = DB::table('articles')->insertGetId([
            'user_id' => $user->id,
            'title' => 'Legacy Article',
            'body' => 'Published before moderation feature',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Re-run forward migrations
        Artisan::call('migrate');

        $this->assertDatabaseHas('artworks', [
            'id' => $legacyArtworkId,
            'status' => Artwork::STATUS_APPROVED,
            'license' => 'all_rights_reserved',
        ]);

        $this->assertDatabaseHas('articles', [
            'id' => $legacyArticleId,
            'status' => Article::STATUS_APPROVED,
        ]);

        $this->get('/explore')->assertOk()->assertSee('Legacy Artwork');
        $this->get('/community')->assertOk()->assertSee('Legacy Article');
    }
}
