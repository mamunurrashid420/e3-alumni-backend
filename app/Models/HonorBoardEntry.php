<?php

namespace App\Models;

use App\Enums\HonorBoardRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HonorBoardEntry extends Model
{
    /** @use HasFactory<\Database\Factories\HonorBoardEntryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role',
        'name',
        'member_id',
        'durations',
        'photo',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => HonorBoardRole::class,
        ];
    }
}
