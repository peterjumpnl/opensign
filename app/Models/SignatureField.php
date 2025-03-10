<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureField extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'document_id',
        'signer_id',
        'field_id',
        'type',
        'page',
        'x_position',
        'y_position',
        'width',
        'height',
        'is_signed',
        'signature_data',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'page' => 'integer',
        'x_position' => 'float',
        'y_position' => 'float',
        'width' => 'float',
        'height' => 'float',
        'is_signed' => 'boolean',
        'signature_data' => 'json',
    ];

    /**
     * Get the document that owns the signature field.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Get the signer that owns the signature field.
     */
    public function signer(): BelongsTo
    {
        return $this->belongsTo(Signer::class);
    }
}
