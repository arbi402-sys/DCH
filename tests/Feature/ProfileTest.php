<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_profile_including_ig_location_city_and_upload_images()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'first_name' => 'Aini',
                'last_name' => 'Putri',
                'username' => 'newusername',
                'bio' => 'Hello world',
                'headline' => 'Visual Designer',
                'institution' => 'ISI Yogyakarta',
                'location' => 'Indonesia',
                'city' => 'Yogyakarta',
                'website' => 'https://aini.design',
                'ig' => 'https://instagram.com/ainidesign',
                'avatar' => UploadedFile::fake()->create('avatar.jpg', 500, 'image/jpeg'),
                'cover_image' => UploadedFile::fake()->create('cover.jpg', 1000, 'image/jpeg'),
            ])
            ->assertSessionHas('success');

        $user->refresh();

        $this->assertEquals('newusername', $user->username);
        $this->assertEquals('Aini Putri', $user->name);
        $this->assertEquals('Indonesia', $user->location);
        $this->assertEquals('Yogyakarta', $user->city);
        $this->assertEquals('https://instagram.com/ainidesign', $user->ig);
        Storage::disk('public')->assertExists($user->avatar);
        Storage::disk('public')->assertExists($user->cover_image);

        // Reload profile page and confirm Instagram link & city/location are rendered
        $this->actingAs($user)
            ->get(route('profile.show', $user->username))
            ->assertOk()
            ->assertSee('https://instagram.com/ainidesign')
            ->assertSee('Yogyakarta, Indonesia');
    }

    public function test_profile_edit_form_has_no_duplicate_location_input_and_rejects_invalid_ig_url()
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user)->get(route('profile.edit'));
        $res->assertOk();
        $this->assertSame(1, substr_count($res->getContent(), 'name="location"'));
        $this->assertSame(1, substr_count($res->getContent(), 'name="city"'));
        $this->assertSame(1, substr_count($res->getContent(), 'name="ig"'));

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'username' => $user->username,
                'ig' => 'not-a-valid-url',
            ])
            ->assertSessionHasErrors(['ig']);
    }

    public function test_user_can_upload_artwork_with_hki_fields()
    {
        Storage::fake('public');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('profile.artworks.store'), [
                'title' => 'My Art',
                'image' => UploadedFile::fake()->create('art.jpg', 750, 'image/jpeg'),
                'license' => 'cc_by',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('artworks', [
            'title' => 'My Art',
            'user_id' => $user->id,
            'license' => 'cc_by',
            'originality_confirmed' => 1,
            'status' => Artwork::STATUS_PENDING,
        ]);
    }
}
