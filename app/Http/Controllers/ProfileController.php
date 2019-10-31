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

    public function store(Request $request): JsonResponse
    {
        $rules = [
            'code' => [
                'required',
                'max:255',
                'unique:profiles,code'
            ],
            'designation' => [
                'required',
                'max:255'
            ],
            'roles' => [
                'array'
            ]
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        $profile = new Profile();
        $profile->code = $request->get('code');
        $profile->designation = $request->get('designation');
        $profile->save();
        if ($request->has('roles')) {
            foreach ($request->get('roles') as $role) {
                DB::table('profile_roles')
                    ->insert([
                        'refProfile' => $profile->id,
                        'refRole' => $role
                    ]);
            }
        }
        return response()->json(Profile::where('id', $profile->id)->with(['roles'])->first(), 200);
    }

    public function show($id): JsonResponse
    {
        $query = Profile::query();
        $query->where('id', $id);
        $query->with(['roles']);
        return response()->json($query->first(), $query->count() == 0 ? 404 : 200);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'code' => [
                'required',
                'max:255',
                new ProfileExists($id),
                'unique:profiles,code,' . $id
            ],
            'designation' => [
                'required',
                'max:255'
            ],
            'roles' => [
                'array'
            ]
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        $profile = Profile::where('id', $id)->first();
        $profile->code = $request->get('code');
        $profile->designation = $request->get('designation');
        $profile->save();

        DB::table('profile_roles')
            ->where('refProfile', $id)
            ->delete();

        if ($request->has('roles')) {
            foreach ($request->get('roles') as $role) {
                DB::table( 'profile_roles')
                    ->insert([
                        'refProfile' => $profile->id,
                        'refRole' => $role
                    ]);
            }
        }
        return response()->json(Profile::where('id', $profile->id)->with(['roles'])->first(), 200);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $rules = [
            'id' => [
                new ProfileExists($id)
            ]
        ];
        $request->request->add(['id' => $id]);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        DB::table('user_profiles')
            ->where('refProfile', $id)
            ->delete();
        DB::table('user_profiles')
            ->where('refProfile', $id)
            ->delete();
        return response()->json(Profile::where('id', $id)->delete(), 200);
    }

}
