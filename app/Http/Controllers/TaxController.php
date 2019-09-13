<?php

namespace App\Http\Controllers;

 use App\Tax;
 use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Traits\Helpers;
use App\Traits\Paginator;
 use Illuminate\Support\Facades\DB;
 use Illuminate\Support\Facades\Validator;

 class TaxController extends Controller
{

    use Paginator;
    use helpers;


    public function index(Request $request ): JsonResponse
    {
        $taxes = Tax::query();
         return response()->json(self::paginate($taxes, $request), 200);

    }

    public function store(Request $request )
    {

        $rules = [
            'name' => 'required|max:255',
            'type' => 'required',
            'rate' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $tax = new Tax;
        $tax->name = $request->get('name');
        $tax->type = $request->get('type');
        $tax->rate = $request->get('rate');
        $tax->save();

         return response()->json('Saved', 200);

    }


    public function update(Request $request )
    {
        $rules = [
            'name' => 'required|max:255',
            'type' => 'required',
            'rate' => 'required|numeric',
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $tax =Tax::find($request->get('id'));
        $tax->name = $request->get('name');
        $tax->type = $request->get('type');
        $tax->rate = $request->get('rate');
        $tax->save();

        return response()->json('Saved', 200);


    }


    public function destroy ($id )
    {


        if($id != 1){

            DB::table('taxes')
                ->where('id', $id)
                ->delete();

            return response()->json('deleted', 200);

        }
        return response()->json('Error Cannot Delete Default', 403);
    }
}
