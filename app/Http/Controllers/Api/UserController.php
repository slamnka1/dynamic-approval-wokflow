<?php

namespace App\Http\Controllers\Api;

use App\Domain\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->select(['id', 'name', 'email', 'role']);

        if ($role = $request->query('role')) {
            $query->where('role', UserRole::from($role));
        }

        return response()->json(['data' => $query->orderBy('name')->get()]);
    }
}
