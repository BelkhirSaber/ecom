<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'key',
        'type',
        'title',
        'title_translations',
        'content',
        'content_translations',
        'order',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'content_translations' => 'array',
        'is_active' => 'boolean',
        'title_translations' => 'array',
    ];

    /**
     * @var array<int, string>
     */
    protected $translatable = [
        'title',
        'content',
    ];
}
