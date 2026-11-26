<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Artwork;
use App\Models\ArtworkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ModerationController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('moderate-content');

        $status = $request->get('status', Artwork::STATUS_PENDING);
        $allowedStatuses = [Artwork::STATUS_PENDING, Artwork::STATUS_APPROVED, Artwork::STATUS_REJECTED, 'all'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = Artwork::STATUS_PENDING;
        }

        $reportStatus = $request->get('report_status', ArtworkReport::STATUS_OPEN);
        $allowedReportStatuses = [
            ArtworkReport::STATUS_OPEN,
            ArtworkReport::STATUS_REVIEWED,
            ArtworkReport::STATUS_RESOLVED,
            ArtworkReport::STATUS_DISMISSED,
            'all',
        ];
        if (! in_array($reportStatus, $allowedReportStatuses, true)) {
            $reportStatus = ArtworkReport::STATUS_OPEN;
        }

        $artworks = Artwork::with(['user', 'category', 'moderator'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15, ['*'], 'artworks_page');

        $articles = Article::with(['user', 'category', 'moderator'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(15, ['*'], 'articles_page');

        $reports = ArtworkReport::with(['artwork.user', 'reporter', 'reviewer'])
            ->when($reportStatus !== 'all', fn ($q) => $q->where('status', $reportStatus))
            ->latest()
            ->paginate(15, ['*'], 'reports_page');

        $counts = [
            'pending_artworks' => Artwork::pending()->count(),
            'pending_articles' => Article::pending()->count(),
            'open_reports' => ArtworkReport::where('status', ArtworkReport::STATUS_OPEN)->count(),
        ];

        return view('admin.moderation', compact('artworks', 'articles', 'reports', 'status', 'reportStatus', 'counts'));
    }

    public function approveArtwork(Request $request, Artwork $artwork)
    {
        Gate::authorize('moderate-content');

        $data = $request->validate([
            'moderation_note' => 'nullable|string|max:1000',
        ]);

        $artwork->status = Artwork::STATUS_APPROVED;
        $artwork->moderation_note = $data['moderation_note'] ?? null;
        $artwork->moderated_by = Auth::id();
        $artwork->moderated_at = now();
        $artwork->save();

        return back()->with('success', 'Karya "'.$artwork->title.'" telah disetujui dan kini tampil publik.');
    }

    public function rejectArtwork(Request $request, Artwork $artwork)
    {
        Gate::authorize('moderate-content');

        $data = $request->validate([
            'moderation_note' => 'required|string|min:5|max:1000',
        ], [
            'moderation_note.required' => 'Alasan penolakan karya wajib diisi.',
            'moderation_note.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $artwork->status = Artwork::STATUS_REJECTED;
        $artwork->moderation_note = $data['moderation_note'];
        $artwork->moderated_by = Auth::id();
        $artwork->moderated_at = now();
        $artwork->save();

        return back()->with('success', 'Karya "'.$artwork->title.'" telah ditolak dengan catatan kurator.');
    }

    public function approveArticle(Request $request, Article $article)
    {
        Gate::authorize('moderate-content');

        $data = $request->validate([
            'moderation_note' => 'nullable|string|max:1000',
        ]);

        $article->status = Article::STATUS_APPROVED;
        $article->moderation_note = $data['moderation_note'] ?? null;
        $article->moderated_by = Auth::id();
        $article->moderated_at = now();
        $article->save();

        return back()->with('success', 'Artikel "'.$article->title.'" telah disetujui dan kini tampil publik.');
    }

    public function rejectArticle(Request $request, Article $article)
    {
        Gate::authorize('moderate-content');

        $data = $request->validate([
            'moderation_note' => 'required|string|min:5|max:1000',
        ], [
            'moderation_note.required' => 'Alasan penolakan artikel wajib diisi.',
            'moderation_note.min' => 'Alasan penolakan minimal 5 karakter.',
        ]);

        $article->status = Article::STATUS_REJECTED;
        $article->moderation_note = $data['moderation_note'];
        $article->moderated_by = Auth::id();
        $article->moderated_at = now();
        $article->save();

        return back()->with('success', 'Artikel "'.$article->title.'" telah ditolak dengan catatan kurator.');
    }

    public function reviewReport(Request $request, ArtworkReport $report)
    {
        Gate::authorize('moderate-content');

        $data = $request->validate([
            'status' => ['required', 'string', Rule::in(array_keys(ArtworkReport::REVIEW_STATUSES))],
            'resolution_note' => ['required', 'string', 'min:5', 'max:1000'],
            'reject_artwork' => ['nullable', 'boolean'],
        ], [
            'resolution_note.required' => 'Catatan hasil peninjauan laporan wajib diisi.',
            'resolution_note.min' => 'Catatan peninjauan minimal 5 karakter.',
        ]);

        $report->status = $data['status'];
        $report->resolution_note = $data['resolution_note'];
        $report->reviewed_by = Auth::id();
        $report->reviewed_at = now();
        $report->save();

        if ($request->boolean('reject_artwork') && $report->artwork) {
            $artwork = $report->artwork;
            $artwork->status = Artwork::STATUS_REJECTED;
            $artwork->moderation_note = 'Diturunkan berdasarkan tinjauan laporan: '.$data['resolution_note'];
            $artwork->moderated_by = Auth::id();
            $artwork->moderated_at = now();
            $artwork->save();
        }

        return back()->with('success', 'Laporan #'.$report->id.' berhasil diperbarui.');
    }
}
