<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Signer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'name',
        'email',
        'access_token',
        'status',
        'order_index',
        'viewed_at',
        'signed_at',
        'declined_at',
        'decline_reason',
        'invited_at',
        'last_reminded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'declined_at' => 'datetime',
        'invited_at' => 'datetime',
        'last_reminded_at' => 'datetime',
    ];

    /**
     * Get the document that owns the signer.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the signatures for the signer.
     */
    public function signatures(): HasMany
    {
        return $this->hasMany(Signature::class);
    }

    /**
     * Get the audit logs for the signer.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Check if the signer has viewed the document.
     */
    public function hasViewed(): bool
    {
        return $this->viewed_at !== null;
    }

    /**
     * Check if the signer has signed the document.
     */
    public function hasSigned(): bool
    {
        return $this->status === 'signed';
    }

    /**
     * Check if the signer has declined to sign the document.
     */
    public function hasDeclined(): bool
    {
        return $this->status === 'declined';
    }
    
    /**
     * Check if the signer has been invited.
     */
    public function hasBeenInvited(): bool
    {
        return $this->invited_at !== null;
    }
    
    /**
     * Get the signing URL for this signer.
     */
    public function getSigningUrl(): string
    {
        return route('sign.show', [
            'signer' => $this->id,
            'document' => $this->document_id,
            'token' => $this->access_token
        ]);
    }
}
