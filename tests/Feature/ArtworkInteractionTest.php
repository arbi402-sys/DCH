<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArtworkInteractionTest extends TestCase
{
    use RefreshDatabase;

    private function createApprovedArtwork(User $author, string $title = 'Test Art'): Artwork
    {
        $art = Artwork::create([
            'user_id' => $author->id,
            'title' => $title,
            'image_path' => 'artworks/test.jpg',
            'license' => 'cc_by',
            'originality_confirmed' => true,
        ]);
        $art->status = Artwork::STATUS_APPROVED;
        $art->save();

        return $art;
    }

    public function test_user_can_like_and_save_artwork_and_appear_on_profile()
    {
        $user = User::factory()->create();
        $author = User::factory()->create();
        $art = $this->createApprovedArtwork($author);

        $this->actingAs($user)->post(route('artworks.like', $art->id))->assertSessionHas('success');
        $this->assertDatabaseHas('artwork_likes', ['user_id' => $user->id, 'artwork_id' => $art->id]);

        $this->actingAs($user)->post(route('artworks.save', $art->id))->assertSessionHas('success');
        $this->assertDatabaseHas('artwork_saves', ['user_id' => $user->id, 'artwork_id' => $art->id]);

        // saved/liked appear on user's profile with accurate data-liked/data-saved attributes
        $res = $this->actingAs($user)->get(route('profile.show', $user->username));
        $res->assertStatus(200);
        $res->assertSee('Saved');
        $res->assertSee('Liked');
        $res->assertSee('data-liked="1"', false);
        $res->assertSee('data-saved="1"', false);
    }

    public function test_user_can_like_then_unlike_and_save_then_unsave_with_consistent_counts_after_reload()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $author = User::factory()->create();
        $art = $this->createApprovedArtwork($author, 'Toggle Artwork');

        // Another user already liked & saved to verify count stays accurate (not hardcoded to 0/1)
        $otherUser->likedArtworks()->attach($art->id);
        $otherUser->savedArtworks()->attach($art->id);

        // 1. Like via JSON
        $this->actingAs($user)
            ->postJson(route('artworks.like', $art->id))
            ->assertOk()
            ->assertJson(['liked' => true, 'like_count' => 2]);
        $this->assertDatabaseHas('artwork_likes', ['user_id' => $user->id, 'artwork_id' => $art->id]);

        // Reload explore & profile -> state is liked=1, count=2
        $exploreLiked = $this->actingAs($user)->get('/explore');
        $exploreLiked->assertOk()
            ->assertSee('data-like-count="2"', false)
            ->assertSee('data-liked="1"', false);

        $profileLiked = $this->actingAs($user)->get(route('profile.show', $author->username));
        $profileLiked->assertOk()
            ->assertSee('data-like-count="2"', false)
            ->assertSee('data-liked="1"', false);

        // 2. Unlike via JSON
        $this->actingAs($user)
            ->postJson(route('artworks.like', $art->id))
            ->assertOk()
            ->assertJson(['liked' => false, 'like_count' => 1]);
        $this->assertDatabaseMissing('artwork_likes', ['user_id' => $user->id, 'artwork_id' => $art->id]);

        // Reload explore & profile -> state is liked=0, count=1
        $this->actingAs($user)->get('/explore')
            ->assertOk()
            ->assertSee('data-like-count="1"', false)
            ->assertSee('data-liked="0"', false);

        $this->actingAs($user)->get(route('profile.show', $author->username))
            ->assertOk()
            ->assertSee('data-like-count="1"', false)
            ->assertSee('data-liked="0"', false);

        // 3. Save via JSON
        $this->actingAs($user)
            ->postJson(route('artworks.save', $art->id))
            ->assertOk()
            ->assertJson(['saved' => true, 'save_count' => 2]);
        $this->assertDatabaseHas('artwork_saves', ['user_id' => $user->id, 'artwork_id' => $art->id]);

        $this->actingAs($user)->get(route('profile.show', $user->username))
            ->assertOk()
            ->assertSee('data-save-count="2"', false)
            ->assertSee('data-saved="1"', false);

        // 4. Unsave via JSON
        $this->actingAs($user)
            ->postJson(route('artworks.save', $art->id))
            ->assertOk()
            ->assertJson(['saved' => false, 'save_count' => 1]);
        $this->assertDatabaseMissing('artwork_saves', ['user_id' => $user->id, 'artwork_id' => $art->id]);

        // Reload explore & detail page -> state is saved=0, count=1
        $this->actingAs($user)->get('/explore')
            ->assertOk()
            ->assertSee('data-save-count="1"', false)
            ->assertSee('data-saved="0"', false);

        $this->actingAs($user)->get(route('artworks.show', $art->id))
            ->assertOk()
            ->assertSee('Save (1)');
    }

    public function test_share_increments_share_count_and_returns_json()
    {
        $author = User::factory()->create();
        $art = $this->createApprovedArtwork($author, 'Share Art');

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
        $res = $this->postJson(route('artworks.share', $art->id));
        $res->assertOk()->assertJsonStructure(['share_count']);
        $this->assertDatabaseHas('artworks', ['id' => $art->id, 'share_count' => 1]);
    }
}
