<?php

namespace App\Models;

use App\Models\Concerns\HasTranslatableAttributes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Page extends Model
{
    use HasFactory;
    use HasTranslatableAttributes;

    protected $fillable = [
        'title',
        'title_translations',
        'slug',
        'content',
        'content_translations',
        'meta_description',
        'meta_description_translations',
        'meta_keywords',
        'is_published',
        'order',
    ];

    protected $casts = [
        'meta_keywords' => 'array',
        'is_published' => 'boolean',
        'title_translations' => 'array',
        'content_translations' => 'array',
        'meta_description_translations' => 'array',
    ];

    /**
     * Translatable attributes that can be localized via *_translations columns.
     *
     * @var array<int, string>
     */
    protected $translatable = [
        'title',
        'content',
        'meta_description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($page) {
            if (!$page->slug) {
                $page->slug = Str::slug($page->title);
            }
        });
    }
}
