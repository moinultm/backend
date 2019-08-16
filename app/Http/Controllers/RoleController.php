<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Role;
use Illuminate\Http\JsonResponse;
use App\Traits\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Rules\RoleExists;

class RoleController extends Controller
{
    use Paginator;

    public function index(Request $request): jsonresponse
   {
       $query = Role::query();
       return response()->json(self::paginate($query, $request), 200);
   }
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'code' => [
                'required',
                'max:255',
                'unique:roles,code'
            ],
            'designation' => [
                'required',
                'max:255'
            ]
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        $role = new Role();
        $role->code = $request->get('code');
        $role->designation = $request->get('designation');
        $role->save();
        return response()->json(Role::where('id', $role->id)->first(), 200);

    }

    public function update(Request $request, $id): JsonResponse
    {
        $rules = [
            'code' => [
                'required',
                'max:255',
                new RoleExists($id),
                'unique:roles,code,' . $id
            ],
            'designation' => [
                'required',
                'max:255'
            ]
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        $role = Role::where('id', $id)->first();
        $role->code = $request->get('code');
        $role->designation = $request->get('designation');
        $role->save();
        return response()->json(Role::where('id', $role->id)->first(), 200);
    }


    public function destroy(Request $request, int $id): JsonResponse
    {
        $rules = [
            'id' => [
                new RoleExists($id)
            ]
        ];
        $request->request->add(['id' => $id]);
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }
        DB::table('profile_roles')
            ->where('refRole', $id)
            ->delete();
        return response()->json(Role::where('id', $id)->delete(), 200);
    }
}
