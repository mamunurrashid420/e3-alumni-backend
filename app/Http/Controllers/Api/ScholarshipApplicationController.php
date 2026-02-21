<?php

namespace App\Http\Controllers\Api;

use App\Enums\ScholarshipApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreScholarshipApplicationRequest;
use App\Http\Resources\Api\ScholarshipApplicationResource;
use App\Models\ScholarshipApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ScholarshipApplicationController extends Controller
{
    /**
     * Store a newly created resource (public; user_id set if authenticated).
     */
    public function store(StoreScholarshipApplicationRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->user()) {
            $data['user_id'] = $request->user()->id;
        }

        if ($request->hasFile('academic_proof_file')) {
            $file = $request->file('academic_proof_file');
            $filename = 'academic_proof_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['academic_proof_file'] = $file->storeAs('scholarship-applications', $filename, 'public');
        }

        if ($request->hasFile('other_document_file')) {
            $file = $request->file('other_document_file');
            $filename = 'other_doc_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['other_document_file'] = $file->storeAs('scholarship-applications', $filename, 'public');
        }

        if ($request->hasFile('applicant_signature')) {
            $file = $request->file('applicant_signature');
            $filename = 'signature_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $data['applicant_signature'] = $file->storeAs('scholarship-applications', $filename, 'public');
        }

        $application = ScholarshipApplication::create($data);

        $application->load('scholarship');

        return (new ScholarshipApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a listing of scholarship applications (super_admin only).
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = ScholarshipApplication::query()->with('scholarship');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('scholarship_id')) {
            $query->where('scholarship_id', $request->scholarship_id);
        }

        $perPage = min(10000, max(1, $request->integer('per_page', 15)));
        $applications = $query->latest()->paginate($perPage);

        return ScholarshipApplicationResource::collection($applications)->response();
    }

    /**
     * Display the specified resource (super_admin only).
     */
    public function show(Request $request, ScholarshipApplication $scholarshipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $scholarshipApplication->load('scholarship');

        return (new ScholarshipApplicationResource($scholarshipApplication))->response();
    }

    /**
     * Approve the scholarship application (super_admin only).
     */
    public function approve(Request $request, ScholarshipApplication $scholarshipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($scholarshipApplication->status !== ScholarshipApplicationStatus::Pending) {
            return response()->json([
                'message' => 'Application is not pending approval.',
            ], 422);
        }

        $scholarshipApplication->update([
            'status' => ScholarshipApplicationStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        $scholarshipApplication->load('scholarship');

        return response()->json([
            'message' => 'Application approved successfully.',
            'data' => new ScholarshipApplicationResource($scholarshipApplication),
        ]);
    }

    /**
     * Reject the scholarship application (super_admin only).
     */
    public function reject(Request $request, ScholarshipApplication $scholarshipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($scholarshipApplication->status !== ScholarshipApplicationStatus::Pending) {
            return response()->json([
                'message' => 'Application is not pending approval.',
            ], 422);
        }

        $rejectedReason = $request->input('rejected_reason');

        $scholarshipApplication->update([
            'status' => ScholarshipApplicationStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejected_reason' => $rejectedReason,
        ]);

        $scholarshipApplication->load('scholarship');

        return response()->json([
            'message' => 'Application rejected successfully.',
            'data' => new ScholarshipApplicationResource($scholarshipApplication),
        ]);
    }
}
