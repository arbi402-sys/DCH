<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArtworkReport extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_DISMISSED = 'dismissed';

    public const REASONS = [
        'plagiarism' => 'Dugaan Plagiarisme',
        'copyright_infringement' => 'Pelanggaran Hak Cipta / HKI',
        'unauthorized_reupload' => 'Unggah Ulang Tanpa Izin Pemilik',
        'inappropriate' => 'Konten Melanggar Etika / Tidak Pantas',
        'other' => 'Lainnya',
    ];

    public const REVIEW_STATUSES = [
        self::STATUS_REVIEWED => 'Sedang Ditinjau',
        self::STATUS_RESOLVED => 'Ditindaklanjuti / Selesai',
        self::STATUS_DISMISSED => 'Ditolak / Tidak Terbukti',
    ];

    protected $fillable = [
        'artwork_id',
        'reporter_id',
        'reason',
        'details',
    ];

    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }

    public function artwork()
    {
        return $this->belongsTo(Artwork::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
