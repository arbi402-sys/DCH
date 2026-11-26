<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_artwork_within_10mb_limit_succeeds_and_above_10mb_is_rejected()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // Exactly at 10 MB (10240 KB) -> accepted
        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'Valid Size Artwork',
                'image' => UploadedFile::fake()->create('valid.jpg', 10240, 'image/jpeg'),
                'license' => 'cc_by',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('artworks', ['title' => 'Valid Size Artwork']);

        // Above 10 MB (10241 KB) -> rejected
        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'Oversized Artwork',
                'image' => UploadedFile::fake()->create('too-big.jpg', 10241, 'image/jpeg'),
                'license' => 'cc_by',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHasErrors(['image']);

        $this->assertDatabaseMissing('artworks', ['title' => 'Oversized Artwork']);

        // Also check profile.artworks.store endpoint rejects > 10 MB
        $this->actingAs($user)
            ->post(route('profile.artworks.store'), [
                'title' => 'Oversized Profile Artwork',
                'image' => UploadedFile::fake()->create('too-big-2.jpg', 10241, 'image/jpeg'),
                'license' => 'all_rights_reserved',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHasErrors(['image']);

        // Unsupported file type (PDF) -> rejected
        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'Invalid Format',
                'image' => UploadedFile::fake()->create('document.pdf', 500, 'application/pdf'),
                'license' => 'all_rights_reserved',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHasErrors(['image']);
    }

    public function test_article_cover_above_10mb_is_rejected()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('upload.article'), [
                'title' => 'Oversized Cover Article',
                'body' => 'Body content for oversized article test.',
                'image' => UploadedFile::fake()->create('huge-cover.png', 10241, 'image/png'),
            ])
            ->assertSessionHasErrors(['image']);

        $this->assertDatabaseMissing('articles', ['title' => 'Oversized Cover Article']);
    }

    public function test_upload_modal_displays_10mb_limit_and_supported_formats()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/explore')
            ->assertOk()
            ->assertSee('10 MB')
            ->assertSee('JPG, JPEG, PNG, WEBP')
            ->assertDontSee('150 MB');
    }
}
