<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Artwork extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const MAX_UPLOAD_KB = 10240; // 10 MB

    public const LICENSES = [
        'all_rights_reserved' => 'All Rights Reserved',
        'cc_by' => 'CC BY 4.0 (Atribusi)',
        'cc_by_nc' => 'CC BY-NC 4.0 (Atribusi-NonKomersial)',
        'cc_by_sa' => 'CC BY-SA 4.0 (Atribusi-BerbagiSerupa)',
        'cc_by_nc_sa' => 'CC BY-NC-SA 4.0 (Atribusi-NonKomersial-BerbagiSerupa)',
        'cc0' => 'CC0 1.0 (Domain Publik)',
    ];

    /**
     * Intentionally excludes moderation columns (status, moderation_note,
     * moderated_by, moderated_at) to prevent mass-assignment bypass.
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'description',
        'image_path',
        'share_count',
        'license',
        'originality_confirmed',
        'copyright_confirmed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'license' => 'all_rights_reserved',
        'originality_confirmed' => false,
    ];

    protected $casts = [
        'share_count' => 'integer',
        'originality_confirmed' => 'boolean',
        'copyright_confirmed_at' => 'datetime',
        'moderated_at' => 'datetime',
    ];

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('artworks.status', self::STATUS_APPROVED);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('artworks.status', self::STATUS_PENDING);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('artworks.status', self::STATUS_REJECTED);
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function getLicenseLabelAttribute(): string
    {
        return self::LICENSES[$this->license] ?? self::LICENSES['all_rights_reserved'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    /**
     * Users who liked this artwork
     */
    public function likedBy()
    {
        return $this->belongsToMany(User::class, 'artwork_likes')->withTimestamps();
    }

    /**
     * Users who saved this artwork
     */
    public function savedBy()
    {
        return $this->belongsToMany(User::class, 'artwork_saves')->withTimestamps();
    }

    /**
     * Plagiarism/copyright reports submitted for this artwork
     */
    public function reports()
    {
        return $this->hasMany(ArtworkReport::class);
    }
}
