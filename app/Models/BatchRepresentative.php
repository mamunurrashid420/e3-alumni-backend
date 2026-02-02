<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BatchRepresentative extends Model
{
    /** @use HasFactory<\Database\Factories\BatchRepresentativeFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'mobile_number',
        'ssc_batch',
        'photo',
        'sort_order',
    ];
}
