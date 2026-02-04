<?php

namespace App\Http\Controllers\Api;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StorePaymentRequest;
use App\Http\Requests\Api\UpdatePaymentRequest;
use App\Http\Resources\Api\PaymentResource;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentApprovedSms;
use App\Services\MoneyReceiptService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        $query = Payment::query();

        // If user is not a super admin, only show their own payments
        if (! $user->isSuperAdmin()) {
            if (! $user->member_id) {
                return response()->json([
                    'data' => [],
                    'links' => [],
                    'meta' => [
                        'current_page' => 1,
                        'from' => null,
                        'last_page' => 1,
                        'path' => $request->url(),
                        'per_page' => (int) $request->integer('per_page', 15),
                        'to' => null,
                        'total' => 0,
                    ],
                ]);
            }
            $query->where('member_id', $user->member_id);
        }

        // Super admins can filter by status and member_id
        if ($user->isSuperAdmin()) {
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('member_id')) {
                $query->where('member_id', $request->member_id);
            }
        }

        $perPage = min(10000, max(1, $request->integer('per_page', 15)));
        $payments = $query->latest()->paginate($perPage);

        return PaymentResource::collection($payments)->response();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        // If user is authenticated and fields are not provided, populate from user data
        if ($user) {
            // Populate name if not provided
            if (empty($data['name'])) {
                $data['name'] = $user->name ?? '';
            }

            // Populate address and mobile_number from membership application if not provided
            if (empty($data['address']) || empty($data['mobile_number'])) {
                $membershipApplication = \App\Models\MembershipApplication::where('email', $user->email)
                    ->where('status', \App\Enums\MembershipApplicationStatus::Approved)
                    ->latest()
                    ->first();

                if ($membershipApplication) {
                    if (empty($data['address'])) {
                        $data['address'] = $membershipApplication->present_address ?? '';
                    }
                    if (empty($data['mobile_number'])) {
                        $data['mobile_number'] = $membershipApplication->mobile_number ?? '';
                    }
                }
            }

            // Populate member_id if not provided and user has one
            if (empty($data['member_id']) && $user->member_id) {
                $data['member_id'] = $user->member_id;
            }
        }

        // Handle file upload
        if ($request->hasFile('payment_proof_file')) {
            $file = $request->file('payment_proof_file');
            $filename = 'payment_proof_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('payments', $filename, 'public');
            $data['payment_proof_file'] = $path;
        }

        // Set default status
        $data['status'] = PaymentStatus::Pending->value;

        $payment = Payment::create($data);

        return (new PaymentResource($payment))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Unauthenticated.');
        }

        // If user is not a super admin, only allow viewing their own payments
        if (! $user->isSuperAdmin()) {
            if ($user->member_id !== $payment->member_id) {
                abort(403, 'Unauthorized action.');
            }
        }

        return (new PaymentResource($payment))->response();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, UpdatePaymentRequest $updateRequest, Payment $payment): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        $data = $updateRequest->validated();

        // Handle file upload
        if ($updateRequest->hasFile('payment_proof_file')) {
            // Delete old file if exists
            if ($payment->payment_proof_file) {
                Storage::disk('public')->delete($payment->payment_proof_file);
            }

            $file = $updateRequest->file('payment_proof_file');
            $filename = 'payment_proof_'.Str::random(20).'.'.$file->getClientOriginalExtension();
            $path = $file->storeAs('payments', $filename, 'public');
            $data['payment_proof_file'] = $path;
        }

        $payment->update($data);

        return (new PaymentResource($payment))->response();
    }

    /**
     * Approve the payment.
     */
    public function approve(Request $request, Payment $payment): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return response()->json([
                'message' => 'Payment is not pending approval.',
            ], 422);
        }

        // Update payment status first
        $payment->update([
            'status' => PaymentStatus::Approved,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        // Reload payment with relationships before generating receipt
        $payment->load('approvedBy');

        // Generate money receipt
        $receiptService = new MoneyReceiptService;
        $receiptPath = $receiptService->generateReceipt($payment);

        // Update payment with receipt file path
        $payment->update([
            'receipt_file' => $receiptPath,
        ]);

        if ($payment->mobile_number) {
            $payment->notify(new PaymentApprovedSms($payment));
        }

        return response()->json([
            'message' => 'Payment approved successfully.',
            'payment' => new PaymentResource($payment),
        ]);
    }

    /**
     * Reject the payment.
     */
    public function reject(Request $request, Payment $payment): JsonResponse
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized action.');
        }

        if ($payment->status !== PaymentStatus::Pending) {
            return response()->json([
                'message' => 'Payment is not pending approval.',
            ], 422);
        }

        $payment->update([
            'status' => PaymentStatus::Rejected,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Payment rejected successfully.',
            'payment' => new PaymentResource($payment),
        ]);
    }

    /**
     * Get member information by member ID for auto-population.
     */
    public function getMemberInfo(Request $request, string $memberId): JsonResponse
    {
        $user = User::where('member_id', $memberId)->first();

        if (! $user) {
            return response()->json([
                'message' => 'Member not found.',
            ], 404);
        }

        // Get address and mobile from membership application if available
        $membershipApplication = \App\Models\MembershipApplication::where('email', $user->email)
            ->latest()
            ->first();

        return response()->json([
            'member_id' => $user->member_id,
            'name' => $user->name,
            'address' => $membershipApplication?->present_address ?? '',
            'mobile_number' => $membershipApplication?->mobile_number ?? '',
        ]);
    }
}
