<?php

namespace App\Http\Controllers\Api;

use App\Enums\MembershipApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\PublicMemberResource;
use App\Models\MembershipApplication;
use App\Models\User;
use App\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMemberController extends Controller
{
    /**
     * Display a paginated listing of members (public).
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('role', UserRole::Member)
            ->whereNotNull('member_id')
            ->with(['secondaryMemberType', 'memberProfile']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ['%'.strtolower($search).'%'])
                    ->orWhereRaw('LOWER(member_id) LIKE ?', ['%'.strtolower($search).'%']);
            });
        }

        if ($request->filled('primary_member_type')) {
            $query->where('primary_member_type', $request->primary_member_type);
        }

        if ($request->boolean('has_secondary_type')) {
            $query->whereNotNull('secondary_member_type_id');
        }

        if ($request->filled('secondary_member_type_id')) {
            $query->where('secondary_member_type_id', $request->secondary_member_type_id);
        }

        $members = $query->latest()->paginate($request->integer('per_page', 12));

        $emails = $members->pluck('email')->filter()->values()->all();
        $phones = $members->pluck('phone')->filter()->values()->all();

        $applications = collect();
        if (! empty($emails) || ! empty($phones)) {
            $appQuery = MembershipApplication::query()
                ->where('status', MembershipApplicationStatus::Approved)
                ->where(function ($q) use ($emails, $phones) {
                    if (! empty($emails)) {
                        $q->whereIn('email', $emails);
                    }
                    if (! empty($phones)) {
                        $q->orWhereIn('mobile_number', $phones);
                    }
                });
            $applications = $appQuery->latest()->get();
        }

        $byEmail = $applications->keyBy('email');
        $byPhone = $applications->keyBy('mobile_number');

        $members->each(function (User $user) use ($byEmail, $byPhone) {
            $app = $byEmail->get($user->email) ?? $byPhone->get($user->phone);
            $user->setRelation('approvedMembershipApplication', $app);
        });

        return PublicMemberResource::collection($members)->response();
    }
}
