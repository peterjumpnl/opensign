<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Signature extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'signer_id',
        'field_id',
        'value',
        'field_type',
        'signed_at',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'signed_at' => 'datetime',
    ];

    /**
     * Get the signer that owns the signature.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(Signer::class);
    }

    /**
     * Get the signature field that this signature belongs to.
     */
    public function field(): BelongsTo
    {
        return $this->belongsTo(SignatureField::class, 'field_id');
    }

    /**
     * Get the audit logs for the signature.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Check if the signature is drawn.
     */
    public function isDrawn(): bool
    {
        return $this->field_type === 'signature';
    }

    /**
     * Check if the signature is typed.
     */
    public function isTyped(): bool
    {
        return $this->field_type === 'typed';
    }

    /**
     * Check if the signature is an initial.
     */
    public function isInitial(): bool
    {
        return $this->field_type === 'initial';
    }
}
