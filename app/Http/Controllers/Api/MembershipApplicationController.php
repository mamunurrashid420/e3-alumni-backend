<?php

namespace App\Http\Controllers\Api;

use App\Enums\MembershipApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreMembershipApplicationRequest;
use App\Http\Requests\Api\UpdateMembershipApplicationRequest;
use App\Http\Resources\Api\MembershipApplicationResource;
use App\Mail\MembershipApprovedMail;
use App\Models\MemberProfile;
use App\Models\MembershipApplication;
use App\Models\User;
use App\Notifications\MembershipApprovedSms;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MembershipApplicationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $query = MembershipApplication::query();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(10000, max(1, $request->integer('per_page', 15)));
        $applications = $query->latest()->paginate($perPage);

        return MembershipApplicationResource::collection($applications)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreMembershipApplicationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Calculate fees based on membership type and payment years
        // Entry fee is shown separately (equals 1 year of membership) but not added to total
        $data['yearly_fee'] = $this->calculateYearlyFee($data['membership_type']);
        $data['total_paid_amount'] = $this->calculateTotalPaidAmount(
            $data['yearly_fee'],
            $data['payment_years'],
            $data['entry_fee'] ?? 0
        );

        // Handle file uploads
        if ($request->hasFile('studentship_proof_file')) {
            $file = $request->file('studentship_proof_file');
            $filename = 'studentship_proof_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['studentship_proof_file'] = $path;
        }

        if ($request->hasFile('receipt_file')) {
            $file = $request->file('receipt_file');
            $filename = 'receipt_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['receipt_file'] = $path;
        }

        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['photo'] = $path;
        }

        if ($request->hasFile('signature')) {
            $file = $request->file('signature');
            $filename = 'signature_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['signature'] = $path;
        }

        $application = MembershipApplication::create($data);

        return (new MembershipApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, MembershipApplication $membershipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        return (new MembershipApplicationResource($membershipApplication))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UpdateMembershipApplicationRequest $updateRequest, MembershipApplication $membershipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $updateRequest->validated();

        // Handle file uploads
        if ($updateRequest->hasFile('studentship_proof_file')) {
            // Delete old file if exists
            if ($membershipApplication->studentship_proof_file) {
                Storage::disk('public')->delete($membershipApplication->studentship_proof_file);
            }

            $file = $updateRequest->file('studentship_proof_file');
            $filename = 'studentship_proof_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['studentship_proof_file'] = $path;
        }

        if ($updateRequest->hasFile('receipt_file')) {
            // Delete old file if exists
            if ($membershipApplication->receipt_file) {
                Storage::disk('public')->delete($membershipApplication->receipt_file);
            }

            $file = $updateRequest->file('receipt_file');
            $filename = 'receipt_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['receipt_file'] = $path;
        }

        if ($updateRequest->hasFile('photo')) {
            // Delete old file if exists
            if ($membershipApplication->photo) {
                Storage::disk('public')->delete($membershipApplication->photo);
            }

            $file = $updateRequest->file('photo');
            $filename = 'photo_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['photo'] = $path;
        }

        if ($updateRequest->hasFile('signature')) {
            // Delete old file if exists
            if ($membershipApplication->signature) {
                Storage::disk('public')->delete($membershipApplication->signature);
            }

            $file = $updateRequest->file('signature');
            $filename = 'signature_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('membership-applications', $filename, 'public');
            $data['signature'] = $path;
        }

        // Calculate fees if membership_type or payment_years are being updated
        if (isset($data['membership_type']) || isset($data['payment_years'])) {
            $membershipType = $data['membership_type'] ?? $membershipApplication->membership_type;
            $paymentYears = $data['payment_years'] ?? $membershipApplication->payment_years;
            $entryFee = $data['entry_fee'] ?? $membershipApplication->entry_fee ?? 0;

            $data['yearly_fee'] = $this->calculateYearlyFee($membershipType);
            $data['total_paid_amount'] = $this->calculateTotalPaidAmount(
                $data['yearly_fee'],
                $paymentYears,
                $entryFee
            );
        }

        $membershipApplication->update($data);

        return (new MembershipApplicationResource($membershipApplication))->response();
    }

    /**
     * Approve the membership application.
     */
    public function approve(Request $request, MembershipApplication $membershipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($membershipApplication->status !== MembershipApplicationStatus::Pending) {
            return response()->json([
                'message' => 'Application is not pending approval.',
            ], 422);
        }

        // Generate member ID
        $memberId = User::generateMemberId(
            $membershipApplication->membership_type,
            $membershipApplication->ssc_year,
            $membershipApplication->jsc_year
        );

        // Generate random 8-digit numerical password
        $password = (string) random_int(10000000, 99999999);

        // Create user - always store phone number from application
        $user = User::create([
            'name' => $membershipApplication->full_name,
            'email' => $membershipApplication->email, // Can be null
            'phone' => $membershipApplication->mobile_number, // Always set from application
            'password' => Hash::make($password),
            'role' => UserRole::Member,
            'primary_member_type' => $membershipApplication->membership_type,
            'member_id' => $memberId,
        ]);

        // Create member profile from application; copy photo/signature to member-profiles path
        $files = MemberProfile::copyFilesFromApplicationPath(
            $membershipApplication->photo,
            $membershipApplication->signature,
            $user->id
        );

        MemberProfile::create([
            'user_id' => $user->id,
            'name_bangla' => $membershipApplication->name_bangla,
            'father_name' => $membershipApplication->father_name,
            'mother_name' => $membershipApplication->mother_name,
            'gender' => $membershipApplication->gender?->value,
            'jsc_year' => $membershipApplication->jsc_year,
            'ssc_year' => $membershipApplication->ssc_year,
            'highest_educational_degree' => $membershipApplication->highest_educational_degree,
            'present_address' => $membershipApplication->present_address,
            'permanent_address' => $membershipApplication->permanent_address,
            'profession' => $membershipApplication->profession,
            'designation' => $membershipApplication->designation,
            'institute_name' => $membershipApplication->institute_name,
            't_shirt_size' => $membershipApplication->t_shirt_size?->value,
            'blood_group' => $membershipApplication->blood_group?->value,
            'photo' => $files['photo'],
            'signature' => $files['signature'],
        ]);

        // Update application status
        $approvedAt = now();
        $membershipApplication->update([
            'status' => MembershipApplicationStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => $approvedAt,
        ]);

        // Set membership expiry for GENERAL/ASSOCIATE (LIFETIME has no expiry)
        if ($membershipApplication->membership_type !== \App\PrimaryMemberType::Lifetime) {
            $expiresAt = User::computeMembershipExpiresAt(
                $approvedAt,
                (int) $membershipApplication->payment_years
            );
            if ($expiresAt) {
                $user->update(['membership_expires_at' => $expiresAt]);
            }
        }

        // Send credentials via email if available
        if ($user->email) {
            Mail::to($user->email)->send(new MembershipApprovedMail($user, $password, $memberId));
        }

        // Always send SMS notification if phone number is available
        if ($user->phone) {
            $user->notify(new MembershipApprovedSms($password, $memberId));
        }

        return response()->json([
            'message' => 'Application approved successfully. User account created.',
            'application' => new MembershipApplicationResource($membershipApplication),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'member_id' => $user->member_id,
            ],
        ]);
    }

    /**
     * Reject the membership application.
     */
    public function reject(Request $request, MembershipApplication $membershipApplication): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($membershipApplication->status !== MembershipApplicationStatus::Pending) {
            return response()->json([
                'message' => 'Application is not pending approval.',
            ], 422);
        }

        $membershipApplication->update([
            'status' => MembershipApplicationStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Application rejected successfully.',
            'application' => new MembershipApplicationResource($membershipApplication),
        ]);
    }

    /**
     * Calculate yearly fee based on membership type.
     */
    private function calculateYearlyFee(string $membershipType): float
    {
        return match ($membershipType) {
            'GENERAL' => 500.0,
            'LIFETIME' => 10000.0,
            'ASSOCIATE' => 300.0,
            default => 0.0,
        };
    }

    /**
     * Calculate total paid amount based on yearly fee and payment years.
     * Entry fee is shown separately and not included in the total.
     */
    private function calculateTotalPaidAmount(float $yearlyFee, string|int $paymentYears, float $entryFee = 0): float
    {
        if (is_string($paymentYears) && strtolower($paymentYears) === 'lifetime') {
            return $yearlyFee;
        }

        return $yearlyFee * (int) $paymentYears;
    }
}
