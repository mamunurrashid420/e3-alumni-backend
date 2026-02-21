<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    /** @use HasFactory<\Database\Factories\NewsFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'description',
        'body',
        'image',
        'author',
        'published_at',
        'is_published',
        'sort_order',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (News $news): void {
            if (empty($news->slug)) {
                $news->slug = static::uniqueSlugFromTitle($news->title);
            }
        });
    }

    /**
     * Generate a unique slug from a title.
     */
    public static function uniqueSlugFromTitle(string $title): string
    {
        $base = Str::slug($title) ?: 'news';
        $slug = $base;
        $n = 0;
        while (static::where('slug', $slug)->exists()) {
            $n++;
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }
}
