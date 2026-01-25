<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MembershipApplication;
use App\Models\Payment;
use App\Models\SelfDeclaration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FileController extends Controller
{
    /**
     * Serve self-declaration signature file.
     */
    public function serveSelfDeclarationSignature(Request $request, SelfDeclaration $selfDeclaration): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Check if user is authenticated and authorized
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins can view any signature
        // Regular users can only view their own signature
        if (! $request->user()->isSuperAdmin() && $selfDeclaration->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized to view this file.'], 403);
        }

        if (! $selfDeclaration->signature_file) {
            return response()->json(['message' => 'Signature file not found.'], 404);
        }

        if (! Storage::disk('public')->exists($selfDeclaration->signature_file)) {
            return response()->json(['message' => 'File does not exist.'], 404);
        }

        return Storage::disk('public')->response($selfDeclaration->signature_file);
    }

    /**
     * Serve payment proof file.
     */
    public function servePaymentProof(Request $request, Payment $payment): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins can view any payment proof
        // Regular users can view payments associated with their member_id
        if (! $request->user()->isSuperAdmin()) {
            $userMemberId = $request->user()->member_id;
            if (! $userMemberId || $payment->member_id !== $userMemberId) {
                return response()->json(['message' => 'Unauthorized to view this file.'], 403);
            }
        }

        if (! $payment->payment_proof_file) {
            return response()->json(['message' => 'Payment proof file not found.'], 404);
        }

        if (! Storage::disk('public')->exists($payment->payment_proof_file)) {
            return response()->json(['message' => 'File does not exist.'], 404);
        }

        return Storage::disk('public')->response($payment->payment_proof_file);
    }

    /**
     * Serve membership application studentship proof file.
     */
    public function serveStudentshipProof(Request $request, MembershipApplication $membershipApplication): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins can view any file
        // Regular users can view their own application files
        if (! $request->user()->isSuperAdmin()) {
            $userEmail = $request->user()->email;
            if (! $userEmail || $membershipApplication->email !== $userEmail) {
                return response()->json(['message' => 'Unauthorized to view this file.'], 403);
            }
        }

        if (! $membershipApplication->studentship_proof_file) {
            return response()->json(['message' => 'Studentship proof file not found.'], 404);
        }

        if (! Storage::disk('public')->exists($membershipApplication->studentship_proof_file)) {
            return response()->json(['message' => 'File does not exist.'], 404);
        }

        return Storage::disk('public')->response($membershipApplication->studentship_proof_file);
    }

    /**
     * Serve membership application receipt file.
     */
    public function serveReceipt(Request $request, MembershipApplication $membershipApplication): StreamedResponse|\Illuminate\Http\JsonResponse
    {
        // Check if user is authenticated
        if (! $request->user()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Super admins can view any file
        // Regular users can view their own application files
        if (! $request->user()->isSuperAdmin()) {
            $userEmail = $request->user()->email;
            if (! $userEmail || $membershipApplication->email !== $userEmail) {
                return response()->json(['message' => 'Unauthorized to view this file.'], 403);
            }
        }

        if (! $membershipApplication->receipt_file) {
            return response()->json(['message' => 'Receipt file not found.'], 404);
        }

        if (! Storage::disk('public')->exists($membershipApplication->receipt_file)) {
            return response()->json(['message' => 'File does not exist.'], 404);
        }

        return Storage::disk('public')->response($membershipApplication->receipt_file);
    }
}
