<?php

namespace App\Models;

use App\Enums\ScholarshipApplicationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScholarshipApplication extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'scholarship_id',
        'applicant_name',
        'applicant_email',
        'applicant_phone',
        'applicant_address',
        'class_or_grade',
        'school_name',
        'parent_or_guardian_name',
        'academic_proof_file',
        'other_document_file',
        'statement',
        'user_id',
        'status',
        'approved_by',
        'approved_at',
        'rejected_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ScholarshipApplicationStatus::class,
            'approved_at' => 'datetime',
        ];
    }

    /**
     * Get the scholarship.
     */
    public function scholarship(): BelongsTo
    {
        return $this->belongsTo(Scholarship::class);
    }

    /**
     * Get the user who submitted (if logged in).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the user who approved this application.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include pending applications.
     */
    public function scopePending($query)
    {
        return $query->where('status', ScholarshipApplicationStatus::Pending);
    }
}
