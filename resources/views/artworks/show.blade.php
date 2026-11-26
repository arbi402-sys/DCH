@extends('layout')

@section('content')
<x-sidebar />
<div class="container" style="padding:24px; max-width:1040px; margin:0 auto;">
    <div style="margin-bottom:16px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <a href="{{ url('/explore') }}" class="btn" style="background:#fff; border:1px solid #ddd; color:#333; text-decoration:none; padding:8px 14px; border-radius:8px;">&larr; Kembali ke Explore</a>
        @auth
            @if(auth()->user()->isAdmin())
                <a href="{{ route('admin.moderation.index') }}" class="btn" style="background:#1f2937; color:#fff; text-decoration:none; padding:8px 14px; border-radius:8px;">Panel Moderasi</a>
            @endif
        @endauth
    </div>

    @if ($errors->any())
        <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 16px; border-radius:8px; margin-bottom:16px;">
            <strong>Terjadi kesalahan:</strong>
            <ul style="margin:6px 0 0 18px; padding:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(!$art->isApproved())
        <div style="padding:14px 16px; border-radius:10px; margin-bottom:16px; {{ $art->isRejected() ? 'background:#fef2f2; border:1px solid #fca5a5; color:#991b1b;' : 'background:#fffbeb; border:1px solid #fcd34d; color:#92400e;' }}">
            <strong>Status Moderasi: {{ strtoupper($art->status) }}</strong>
            @if($art->isPending())
                <p style="margin:6px 0 0 0;">Karya ini sedang menunggu persetujuan kurator/admin dan belum tampil di halaman publik.</p>
            @elseif($art->isRejected())
                <p style="margin:6px 0 0 0;">Karya ini ditolak oleh kurator/admin dan tidak ditampilkan secara publik.</p>
                @if($art->moderation_note)
                    <p style="margin:6px 0 0 0;"><strong>Alasan Penolakan:</strong> {{ $art->moderation_note }}</p>
                @endif
            @endif
        </div>
    @endif

    <div style="background:#fff; border-radius:14px; padding:24px; box-shadow:0 8px 24px rgba(0,0,0,0.05); display:grid; grid-template-columns:minmax(0, 1.3fr) minmax(0, 1fr); gap:24px;">
        <div>
            <img src="{{ asset('storage/'.$art->image_path) }}" alt="{{ $art->title }}" style="width:100%; max-height:560px; object-fit:contain; background:#f8f9fa; border-radius:10px;">
            <div style="color:#666; font-size:13px; margin-top:10px;">
                Diunggah {{ optional($art->created_at)->diffForHumans() }}
                @if($art->category)
                    &middot; Kategori: <strong>{{ $art->category->name }}</strong>
                @endif
            </div>
        </div>

        <div style="display:flex; flex-direction:column; gap:14px;">
            <div style="display:flex; align-items:center; gap:12px;">
                <img src="{{ optional($art->user)->avatar_url ?? '/images/icon/profil.png' }}" alt="Avatar" style="width:48px; height:48px; border-radius:50%; object-fit:cover;">
                <div>
                    @if($art->user)
                        <a href="{{ route('profile.show', $art->user->username ?: $art->user->id) }}" style="text-decoration:none; color:#111; font-weight:700;">{{ $art->user->name ?? $art->user->username }}</a>
                        <div style="color:#666; font-size:13px;">{{ '@'.($art->user->username ?? '') }}</div>
                    @endif
                </div>
            </div>

            <div>
                <h1 style="margin:0 0 8px 0; font-size:24px; color:#111;">{{ $art->title }}</h1>
                <p style="margin:0; color:#444; line-height:1.6;">{{ $art->description ?: 'Deskripsi karya belum tersedia.' }}</p>
            </div>

            <!-- HKI & License Card -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px 14px;">
                <div style="font-size:12px; text-transform:uppercase; letter-spacing:0.04em; color:#64748b; font-weight:700;">Lisensi &amp; Hak Cipta</div>
                <div style="margin-top:6px; display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <span id="artworkLicenseBadge" style="background:#e0f2fe; color:#0369a1; font-weight:700; font-size:13px; padding:4px 10px; border-radius:999px;">{{ $art->license_label }}</span>
                    @if($art->originality_confirmed)
                        <span style="background:#dcfce7; color:#166534; font-size:12px; font-weight:600; padding:4px 10px; border-radius:999px;">Pernyataan Orisinalitas Terkonfirmasi</span>
                    @endif
                </div>
            </div>

            <!-- Interactions -->
            <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                @auth
                    <form method="POST" action="{{ route('artworks.like', $art->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:999px; border:1px solid {{ $isLiked ? '#ef4444' : '#ddd' }}; background:{{ $isLiked ? '#fef2f2' : '#fff' }}; color:#111; cursor:pointer; font-weight:600;">
                            <img src="{{ $isLiked ? '/images/icon/like.png' : '/images/icon/unlike.png' }}" alt="like" style="width:16px; height:16px;">
                            <span>{{ $isLiked ? 'Unlike' : 'Like' }} ({{ $art->liked_by_count }})</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ route('artworks.save', $art->id) }}" style="margin:0;">
                        @csrf
                        <button type="submit" style="display:inline-flex; align-items:center; gap:6px; padding:8px 14px; border-radius:999px; border:1px solid {{ $isSaved ? '#2563eb' : '#ddd' }}; background:{{ $isSaved ? '#eff6ff' : '#fff' }}; color:#111; cursor:pointer; font-weight:600;">
                            <img src="{{ $isSaved ? '/images/icon/save.png' : '/images/icon/unsave.png' }}" alt="save" style="width:16px; height:16px;">
                            <span>{{ $isSaved ? 'Unsave' : 'Save' }} ({{ $art->saved_by_count }})</span>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" style="padding:8px 14px; border-radius:999px; border:1px solid #ddd; color:#333; text-decoration:none; font-size:13px;">Login untuk Like ({{ $art->liked_by_count }}) / Save ({{ $art->saved_by_count }})</a>
                @endauth
            </div>

            <!-- Report Plagiarism / Copyright Infringement -->
            @if($art->isApproved())
                <div id="report-section" style="margin-top:8px; border-top:1px solid #eee; padding-top:14px;">
                    <h3 style="margin:0 0 6px 0; font-size:15px; color:#991b1b;">Laporkan Dugaan Pelanggaran / Plagiarisme</h3>
                    <p style="margin:0 0 10px 0; font-size:13px; color:#666;">Jika Anda menemukan indikasi plagiarisme atau pelanggaran hak cipta, kirimkan laporan di bawah ini untuk ditinjau secara manual oleh admin/kurator.</p>

                    @auth
                        @if(auth()->id() !== $art->user_id)
                            <form method="POST" action="{{ route('artworks.report', $art->id) }}" style="display:flex; flex-direction:column; gap:10px;">
                                @csrf
                                <div>
                                    <label for="reportReason" style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Alasan Pelaporan</label>
                                    <select id="reportReason" name="reason" required style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px;">
                                        <option value="">-- Pilih Alasan --</option>
                                        @foreach($reportReasons as $key => $label)
                                            <option value="{{ $key }}" {{ old('reason') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="reportDetails" style="display:block; font-size:13px; font-weight:600; margin-bottom:4px;">Detail Laporan &amp; Bukti Pendukung (URL sumber asli/penjelasan)</label>
                                    <textarea id="reportDetails" name="details" rows="3" required minlength="10" placeholder="Jelaskan alasan pelaporan dan sertakan referensi karya asli jika ada..." style="width:100%; padding:8px 10px; border:1px solid #d1d5db; border-radius:8px;">{{ old('details') }}</textarea>
                                </div>
                                <div>
                                    <button type="submit" style="background:#dc2626; color:#fff; border:none; padding:8px 16px; border-radius:8px; cursor:pointer; font-weight:600;">Kirim Laporan</button>
                                </div>
                            </form>
                        @else
                            <div style="font-size:13px; color:#666;">Anda adalah pemilik karya ini.</div>
                        @endif
                    @else
                        <a href="{{ route('login') }}" style="font-size:13px; color:#dc2626; text-decoration:underline;">Masuk untuk melaporkan dugaan pelanggaran karya ini</a>
                    @endauth
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
