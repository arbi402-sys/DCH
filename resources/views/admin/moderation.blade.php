@extends('layout')

@section('content')
<x-sidebar />
<div class="container" style="padding:24px; max-width:1200px; margin:0 auto;">
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:20px;">
        <div>
            <h1 style="margin:0; font-size:26px; color:#111;">Panel Kurasi &amp; Moderasi Konten</h1>
            <p style="margin:4px 0 0 0; color:#555; font-size:14px;">Tinjau karya baru, artikel baru, serta laporan dugaan plagiarisme atau pelanggaran HKI secara manual.</p>
        </div>
        <a href="{{ url('/explore') }}" style="background:#fff; border:1px solid #d1d5db; color:#111; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:600;">Kembali ke Explore</a>
    </div>

    @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            <strong>Validasi gagal:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Summary Cards -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:14px; margin-bottom:20px;">
        <div style="background:#fff; padding:16px; border-radius:12px; border:1px solid #e5e7eb;">
            <div style="font-size:13px; color:#6b7280;">Karya Menunggu Kurasi</div>
            <div style="font-size:28px; font-weight:700; color:#d97706;">{{ $counts['pending_artworks'] }}</div>
        </div>
        <div style="background:#fff; padding:16px; border-radius:12px; border:1px solid #e5e7eb;">
            <div style="font-size:13px; color:#6b7280;">Artikel Menunggu Kurasi</div>
            <div style="font-size:28px; font-weight:700; color:#2563eb;">{{ $counts['pending_articles'] }}</div>
        </div>
        <div style="background:#fff; padding:16px; border-radius:12px; border:1px solid #e5e7eb;">
            <div style="font-size:13px; color:#6b7280;">Laporan Pelanggaran Terbuka</div>
            <div style="font-size:28px; font-weight:700; color:#dc2626;">{{ $counts['open_reports'] }}</div>
        </div>
    </div>

    <!-- Filter bar -->
    <div style="display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px;">
        @foreach(['pending' => 'Menunggu', 'approved' => 'Disetujui', 'rejected' => 'Ditolak', 'all' => 'Semua'] as $key => $label)
            <a href="{{ route('admin.moderation.index', ['status' => $key, 'report_status' => $reportStatus]) }}"
               style="padding:8px 14px; border-radius:999px; text-decoration:none; font-size:13px; font-weight:600; {{ $status === $key ? 'background:#111; color:#fff;' : 'background:#fff; color:#374151; border:1px solid #d1d5db;' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <!-- Artworks Moderation Section -->
    <section style="background:#fff; border-radius:12px; padding:18px; border:1px solid #e5e7eb; margin-bottom:24px;">
        <h2 style="margin:0 0 14px 0; font-size:18px;">Kurasi Karya ({{ $artworks->total() }})</h2>
        @forelse($artworks as $artwork)
            <div style="border-top:1px solid #f3f4f6; padding:14px 0; display:grid; grid-template-columns:120px minmax(0, 1fr) 320px; gap:16px; align-items:start;">
                <div>
                    <img src="{{ asset('storage/'.$artwork->image_path) }}" alt="{{ $artwork->title }}" style="width:120px; height:90px; object-fit:cover; border-radius:8px; background:#f3f4f6;">
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('artworks.show', $artwork->id) }}" style="font-weight:700; color:#111; text-decoration:none;">{{ $artwork->title }}</a>
                        <span style="font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px; {{ $artwork->isApproved() ? 'background:#dcfce7;color:#166534;' : ($artwork->isRejected() ? 'background:#fee2e2;color:#991b1b;' : 'background:#fef3c7;color:#92400e;') }}">{{ strtoupper($artwork->status) }}</span>
                    </div>
                    <div style="font-size:13px; color:#555; margin-top:4px;">
                        Pengunggah: <strong>{{ optional($artwork->user)->username ?? optional($artwork->user)->name ?? 'Unknown' }}</strong>
                        &middot; Lisensi: <strong>{{ $artwork->license_label }}</strong>
                        &middot; Orisinalitas: <strong>{{ $artwork->originality_confirmed ? 'Terkonfirmasi' : 'Belum terkonfirmasi' }}</strong>
                    </div>
                    @if($artwork->description)
                        <p style="margin:6px 0 0 0; font-size:13px; color:#444;">{{ \Illuminate\Support\Str::limit($artwork->description, 160) }}</p>
                    @endif
                    @if($artwork->moderation_note)
                        <div style="margin-top:6px; font-size:12px; color:#7f1d1d; background:#fef2f2; padding:6px 10px; border-radius:6px;">
                            <strong>Catatan kurator:</strong> {{ $artwork->moderation_note }}
                        </div>
                    @endif
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <form method="POST" action="{{ route('admin.moderation.artworks.approve', $artwork->id) }}" style="display:flex; gap:6px;">
                        @csrf
                        <input type="text" name="moderation_note" placeholder="Catatan opsional..." style="flex:1; padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:12px;">
                        <button type="submit" style="background:#16a34a; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('admin.moderation.artworks.reject', $artwork->id) }}" style="display:flex; gap:6px;">
                        @csrf
                        <input type="text" name="moderation_note" required minlength="5" placeholder="Alasan penolakan (wajib)..." style="flex:1; padding:6px 8px; border:1px solid #fca5a5; border-radius:6px; font-size:12px;">
                        <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">Tolak</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color:#666; margin:0;">Tidak ada karya pada filter status ini.</p>
        @endforelse
        <div style="margin-top:12px;">{{ $artworks->withQueryString()->links() }}</div>
    </section>

    <!-- Articles Moderation Section -->
    <section style="background:#fff; border-radius:12px; padding:18px; border:1px solid #e5e7eb; margin-bottom:24px;">
        <h2 style="margin:0 0 14px 0; font-size:18px;">Kurasi Artikel ({{ $articles->total() }})</h2>
        @forelse($articles as $article)
            <div style="border-top:1px solid #f3f4f6; padding:14px 0; display:grid; grid-template-columns:minmax(0, 1fr) 320px; gap:16px; align-items:start;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <a href="{{ route('articles.show', $article->id) }}" style="font-weight:700; color:#111; text-decoration:none;">{{ $article->title }}</a>
                        <span style="font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px; {{ $article->isApproved() ? 'background:#dcfce7;color:#166534;' : ($article->isRejected() ? 'background:#fee2e2;color:#991b1b;' : 'background:#fef3c7;color:#92400e;') }}">{{ strtoupper($article->status) }}</span>
                    </div>
                    <div style="font-size:13px; color:#555; margin-top:4px;">
                        Penulis: <strong>{{ optional($article->user)->username ?? optional($article->user)->name ?? 'Unknown' }}</strong>
                        &middot; {{ optional($article->created_at)->diffForHumans() }}
                    </div>
                    <p style="margin:6px 0 0 0; font-size:13px; color:#444;">{{ \Illuminate\Support\Str::limit(strip_tags($article->body), 180) }}</p>
                    @if($article->moderation_note)
                        <div style="margin-top:6px; font-size:12px; color:#7f1d1d; background:#fef2f2; padding:6px 10px; border-radius:6px;">
                            <strong>Catatan kurator:</strong> {{ $article->moderation_note }}
                        </div>
                    @endif
                </div>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    <form method="POST" action="{{ route('admin.moderation.articles.approve', $article->id) }}" style="display:flex; gap:6px;">
                        @csrf
                        <input type="text" name="moderation_note" placeholder="Catatan opsional..." style="flex:1; padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:12px;">
                        <button type="submit" style="background:#16a34a; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">Setujui</button>
                    </form>
                    <form method="POST" action="{{ route('admin.moderation.articles.reject', $article->id) }}" style="display:flex; gap:6px;">
                        @csrf
                        <input type="text" name="moderation_note" required minlength="5" placeholder="Alasan penolakan (wajib)..." style="flex:1; padding:6px 8px; border:1px solid #fca5a5; border-radius:6px; font-size:12px;">
                        <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">Tolak</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="color:#666; margin:0;">Tidak ada artikel pada filter status ini.</p>
        @endforelse
        <div style="margin-top:12px;">{{ $articles->withQueryString()->links() }}</div>
    </section>

    <!-- Plagiarism / HKI Reports Section -->
    <section style="background:#fff; border-radius:12px; padding:18px; border:1px solid #e5e7eb;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:14px;">
            <h2 style="margin:0; font-size:18px;">Laporan Dugaan Pelanggaran / Plagiarisme ({{ $reports->total() }})</h2>
            <div style="display:flex; gap:6px; flex-wrap:wrap;">
                @foreach(['open' => 'Terbuka', 'reviewed' => 'Ditinjau', 'resolved' => 'Selesai', 'dismissed' => 'Ditolak', 'all' => 'Semua'] as $rKey => $rLabel)
                    <a href="{{ route('admin.moderation.index', ['status' => $status, 'report_status' => $rKey]) }}"
                       style="padding:4px 10px; border-radius:999px; text-decoration:none; font-size:12px; font-weight:600; {{ $reportStatus === $rKey ? 'background:#dc2626; color:#fff;' : 'background:#f3f4f6; color:#374151;' }}">
                        {{ $rLabel }}
                    </a>
                @endforeach
            </div>
        </div>

        @forelse($reports as $report)
            <div style="border-top:1px solid #f3f4f6; padding:14px 0; display:grid; grid-template-columns:minmax(0, 1fr) 340px; gap:16px; align-items:start;">
                <div>
                    <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                        <strong>Laporan #{{ $report->id }}</strong>
                        <span style="font-size:12px; font-weight:700; padding:2px 8px; border-radius:999px; background:#fee2e2; color:#991b1b;">{{ $report->reason_label }}</span>
                        <span style="font-size:12px; font-weight:600; padding:2px 8px; border-radius:999px; background:#e5e7eb; color:#374151;">{{ strtoupper($report->status) }}</span>
                    </div>
                    <div style="font-size:13px; color:#555; margin-top:4px;">
                        Karya:
                        @if($report->artwork)
                            <a href="{{ route('artworks.show', $report->artwork->id) }}" style="font-weight:600;">{{ $report->artwork->title }}</a>
                            (oleh {{ optional($report->artwork->user)->username ?? 'Unknown' }})
                        @else
                            <em>Karya telah dihapus</em>
                        @endif
                        &middot; Pelapor: <strong>{{ optional($report->reporter)->username ?? 'Unknown' }}</strong>
                    </div>
                    <p style="margin:6px 0 0 0; font-size:13px; color:#333;">{{ $report->details }}</p>
                    @if($report->resolution_note)
                        <div style="margin-top:6px; font-size:12px; color:#1e3a8a; background:#eff6ff; padding:6px 10px; border-radius:6px;">
                            <strong>Hasil peninjauan:</strong> {{ $report->resolution_note }}
                        </div>
                    @endif
                </div>
                <form method="POST" action="{{ route('admin.moderation.reports.review', $report->id) }}" style="display:flex; flex-direction:column; gap:8px;">
                    @csrf
                    <select name="status" required style="padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:12px;">
                        @foreach(\App\Models\ArtworkReport::REVIEW_STATUSES as $sKey => $sLabel)
                            <option value="{{ $sKey }}" {{ $report->status === $sKey ? 'selected' : '' }}>{{ $sLabel }}</option>
                        @endforeach
                    </select>
                    <textarea name="resolution_note" rows="2" required minlength="5" placeholder="Catatan hasil peninjauan manual..." style="padding:6px 8px; border:1px solid #d1d5db; border-radius:6px; font-size:12px;">{{ $report->resolution_note }}</textarea>
                    <label style="font-size:12px; color:#991b1b; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" name="reject_artwork" value="1">
                        Turunkan karya (ubah status karya menjadi Rejected)
                    </label>
                    <button type="submit" style="background:#1f2937; color:#fff; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-size:12px; font-weight:600;">Simpan Tinjauan</button>
                </form>
            </div>
        @empty
            <p style="color:#666; margin:0;">Tidak ada laporan pada filter status ini.</p>
        @endforelse
        <div style="margin-top:12px;">{{ $reports->withQueryString()->links() }}</div>
    </section>
</div>
@endsection
