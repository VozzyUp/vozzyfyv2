<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAreaDomain extends Model
{
    public const TYPE_PATH = 'path';
    public const TYPE_SUBDOMAIN = 'subdomain';
    public const TYPE_CUSTOM = 'custom';

    protected $fillable = ['product_id', 'type', 'value'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
