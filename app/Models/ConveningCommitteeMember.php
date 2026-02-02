<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConveningCommitteeMember extends Model
{
    /** @use HasFactory<\Database\Factories\ConveningCommitteeMemberFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'mobile_number',
        'designation',
        'occupation',
        'photo',
        'sort_order',
    ];
}
