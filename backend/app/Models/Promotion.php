<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'name',
        'name_translations',
        'type',
        'discount_type',
        'discount_value',
        'applicable_products',
        'applicable_categories',
        'min_order_amount',
        'priority',
        'starts_at',
        'expires_at',
        'is_active',
        'description',
        'description_translations',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'applicable_products' => 'array',
        'applicable_categories' => 'array',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'name_translations' => 'array',
        'description_translations' => 'array',
    ];

    /**
     * @var array<int, string>
     */
    protected $translatable = [
        'name',
        'description',
    ];

    public function isActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && now()->isBefore($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && now()->isAfter($this->expires_at)) {
            return false;
        }

        return true;
    }
}
