<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MemberType;
use Illuminate\Http\JsonResponse;

class MemberTypeController extends Controller
{
    /**
     * Display a listing of all member types.
     */
    public function index(): JsonResponse
    {
        $memberTypes = MemberType::orderBy('name')->get();

        return response()->json([
            'data' => $memberTypes->map(function ($memberType) {
                return [
                    'id' => $memberType->id,
                    'name' => $memberType->name,
                    'description' => $memberType->description,
                ];
            }),
        ]);
    }
}
