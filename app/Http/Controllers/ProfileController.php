<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Traits\Paginator;
use App\Profile;
use App\Rules\ProfileExists;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    use Paginator;

    public function index(Request $request): JsonResponse
    {
        $query = Profile::query();
        $query->with(['roles']);
        return response()->json(self::paginate($query, $request), 200);
    }
}
