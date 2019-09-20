<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RepresentativeController extends Controller
{

    public function getUser(): JsonResponse {

        $query = User::query();
        //$query->where('user_type', '2');
        $AssociateArray = array(
            'data' =>  $query->get()
        );


        return response()->json( $AssociateArray, 200);
    }

}
