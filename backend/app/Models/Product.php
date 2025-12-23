<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** @mixin \App\Models\StockMovement */

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'category_id',
        'type',
        'sku',
        'name',
        'name_translations',
        'slug',
        'short_description',
        'short_description_translations',
        'description',
        'description_translations',
        'price',
        'compare_price',
        'cost_price',
        'currency',
        'stock_quantity',
        'stock_status',
        'is_active',
        'weight',
        'width',
        'height',
        'length',
        'attributes',
        'meta_title',
        'meta_title_translations',
        'meta_description',
        'meta_description_translations',
        'meta_keywords',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'is_active' => 'bool',
        'weight' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'length' => 'decimal:2',
        'attributes' => 'array',
        'published_at' => 'datetime',
        'name_translations' => 'array',
        'short_description_translations' => 'array',
        'description_translations' => 'array',
        'meta_title_translations' => 'array',
        'meta_description_translations' => 'array',
    ];

    /**
     * @var array<int, string>
     */
    protected $translatable = [
        'name',
        'short_description',
        'description',
        'meta_title',
        'meta_description',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'stockable')->latest();
    }
}
