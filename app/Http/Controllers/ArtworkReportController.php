<?php

namespace App\Http\Controllers;

use App\Models\Artwork;
use App\Models\ArtworkReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ArtworkReportController extends Controller
{
    public function store(Request $request, Artwork $art)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        if (! $art->isApproved() && ! $user->isAdmin()) {
            abort(404);
        }

        if ($art->user_id === $user->id) {
            $msg = 'Anda tidak dapat melaporkan karya milik sendiri.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

        $data = $request->validate([
            'reason' => ['required', 'string', Rule::in(array_keys(ArtworkReport::REASONS))],
            'details' => ['required', 'string', 'min:10', 'max:2000'],
        ], [
            'reason.required' => 'Pilih alasan pelaporan terlebih dahulu.',
            'reason.in' => 'Alasan pelaporan tidak valid.',
            'details.required' => 'Jelaskan detail dugaan pelanggaran atau plagiarisme.',
            'details.min' => 'Detail laporan minimal 10 karakter agar dapat ditinjau kurator.',
        ]);

        $report = new ArtworkReport([
            'artwork_id' => $art->id,
            'reporter_id' => $user->id,
            'reason' => $data['reason'],
            'details' => $data['details'],
        ]);
        $report->status = ArtworkReport::STATUS_OPEN;
        $report->save();

        $successMessage = 'Laporan dugaan pelanggaran telah dikirim dan akan ditinjau secara manual oleh admin/kurator.';

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'report_id' => $report->id,
            ], 201);
        }

        return back()->with('success', $successMessage);
    }
}
