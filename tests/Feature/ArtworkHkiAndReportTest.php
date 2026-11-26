<?php

namespace Tests\Feature;

use App\Models\Artwork;
use App\Models\ArtworkReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArtworkHkiAndReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_artwork_upload_requires_valid_license_and_originality_confirmation()
    {
        Storage::fake('public');
        $user = User::factory()->create();

        // Missing license and originality confirmation
        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'No HKI Artwork',
                'image' => UploadedFile::fake()->create('art.jpg', 500, 'image/jpeg'),
            ])
            ->assertSessionHasErrors(['license', 'originality_confirmed']);

        // Invalid license value
        $this->actingAs($user)
            ->post(route('upload.artwork'), [
                'title' => 'Invalid License Artwork',
                'image' => UploadedFile::fake()->create('art.jpg', 500, 'image/jpeg'),
                'license' => 'invalid_license',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHasErrors(['license']);

        $this->assertDatabaseCount('artworks', 0);
    }

    public function test_artwork_license_is_stored_and_displayed_on_artwork_detail_page()
    {
        Storage::fake('public');
        $author = User::factory()->create();

        $this->actingAs($author)
            ->post(route('upload.artwork'), [
                'title' => 'Licensed Poster',
                'description' => 'Poster karya asli mahasiswa',
                'image' => UploadedFile::fake()->create('poster.png', 400, 'image/png'),
                'license' => 'cc_by_sa',
                'originality_confirmed' => '1',
            ])
            ->assertSessionHas('success');

        $art = Artwork::where('title', 'Licensed Poster')->firstOrFail();
        $this->assertEquals('cc_by_sa', $art->license);
        $this->assertTrue($art->originality_confirmed);
        $this->assertNotNull($art->copyright_confirmed_at);

        // Approve so public can view detail page
        $art->status = Artwork::STATUS_APPROVED;
        $art->save();

        $this->get(route('artworks.show', $art->id))
            ->assertOk()
            ->assertSee('CC BY-SA 4.0 (Atribusi-BerbagiSerupa)')
            ->assertSee('Pernyataan Orisinalitas Terkonfirmasi');

        $this->get('/explore')
            ->assertOk()
            ->assertSee('data-license-label="CC BY-SA 4.0 (Atribusi-BerbagiSerupa)"', false);
    }

    public function test_user_can_submit_plagiarism_report_and_admin_can_review_it()
    {
        $author = User::factory()->create(['role' => 'mahasiswa']);
        $reporter = User::factory()->create(['role' => 'mahasiswa']);
        $admin = User::factory()->create(['role' => 'admin']);

        $art = Artwork::create([
            'user_id' => $author->id,
            'title' => 'Suspected Artwork',
            'image_path' => 'artworks/suspected.jpg',
            'license' => 'all_rights_reserved',
            'originality_confirmed' => true,
        ]);
        $art->status = Artwork::STATUS_APPROVED;
        $art->save();

        // Owner cannot report their own artwork
        $this->actingAs($author)
            ->post(route('artworks.report', $art->id), [
                'reason' => 'plagiarism',
                'details' => 'Self report should not be accepted by the system.',
            ])
            ->assertSessionHas('error');

        // Validation rejects short details
        $this->actingAs($reporter)
            ->post(route('artworks.report', $art->id), [
                'reason' => 'plagiarism',
                'details' => 'short',
            ])
            ->assertSessionHasErrors(['details']);

        // Valid report by another user
        $this->actingAs($reporter)
            ->post(route('artworks.report', $art->id), [
                'reason' => 'plagiarism',
                'details' => 'Karya ini identik dengan ilustrasi pada portofolio publik https://example.com/original.',
                'status' => 'resolved', // attempt to tamper status must be ignored
            ])
            ->assertSessionHas('success');

        $report = ArtworkReport::firstOrFail();
        $this->assertEquals($art->id, $report->artwork_id);
        $this->assertEquals($reporter->id, $report->reporter_id);
        $this->assertEquals('plagiarism', $report->reason);
        $this->assertEquals(ArtworkReport::STATUS_OPEN, $report->status);

        // Non-admin cannot review reports
        $this->actingAs($reporter)
            ->post(route('admin.moderation.reports.review', $report->id), [
                'status' => ArtworkReport::STATUS_RESOLVED,
                'resolution_note' => 'Trying to resolve without admin role.',
            ])
            ->assertStatus(403);

        // Admin reviews report and rejects artwork
        $this->actingAs($admin)
            ->post(route('admin.moderation.reports.review', $report->id), [
                'status' => ArtworkReport::STATUS_RESOLVED,
                'resolution_note' => 'Terverifikasi melanggar hak cipta berdasarkan bukti sumber asli.',
                'reject_artwork' => '1',
            ])
            ->assertSessionHas('success');

        $report->refresh();
        $art->refresh();
        $this->assertEquals(ArtworkReport::STATUS_RESOLVED, $report->status);
        $this->assertEquals($admin->id, $report->reviewed_by);
        $this->assertNotNull($report->reviewed_at);
        $this->assertEquals(Artwork::STATUS_REJECTED, $art->status);
        $this->assertStringContainsString('melanggar hak cipta', $art->moderation_note);
    }
}
