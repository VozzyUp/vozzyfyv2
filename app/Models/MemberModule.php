<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemberModule extends Model
{
    protected $fillable = ['member_section_id', 'product_id', 'title', 'position', 'thumbnail', 'show_title_on_cover', 'related_product_id', 'access_type', 'external_url'];

    protected function casts(): array
    {
        return ['position' => 'integer', 'show_title_on_cover' => 'boolean'];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(MemberSection::class, 'member_section_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function relatedProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'related_product_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(MemberLesson::class, 'member_module_id')->orderBy('position');
    }
}
