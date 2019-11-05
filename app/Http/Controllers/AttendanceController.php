<?php

namespace App\Http\Controllers;

use App\Attendance;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Traits\Helpers;


class AttendanceController extends Controller
{
    use helpers;


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request): JsonResponse
    {

        $transactions = Attendance::query()->orderBy('date', 'desc') ;


        $from = $request->get('from');
        $to=$request->get('to');

        if( $request->get('from') !='null' &&  $request->get('to')!='null' ) {
            $from = $request->get('from');
            $to = $request->get('to')?:date('Y-m-d');
            $to = Carbon::createFromFormat('Y-m-d',$to);
            $to = self::filterTo($to);
        }


        if( $request->get('from') !='null' &&   $request->get('to')!='null' ) {

            if($request->get('from') || $request->get('to')) {
                if(!is_null($from)){
                    $from = Carbon::createFromFormat('Y-m-d',$from);
                    $from = self::filterFrom($from);
                    $transactions->whereBetween('attendances.date',[$from,$to]);
                }else{
                    $transactions->where('attendances.date','<=',$to);
                }
            }
        }


        $size = $request->size;

        return response()->json( $transactions->paginate($size), 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function clockIn()
    {
        return response()->json( '', 200);
    }

    public function clockOut()
    {
        return response()->json( '', 200);
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'employee_id' => ['required']
        ];

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return response()->json(collect($validator->getMessageBag())->flatten()->toArray(), 403);
        }

        $attendance = new Attendance();
        $attendance->employee_id = $request->get('employee_id');
        $attendance->date = Carbon::parse($request->get('date'))->format('Y-m-d');
        $attendance->clock_in =Carbon::createFromFormat('H:i:s',$request->get('clock_in'))->format('h:i');
        $attendance->clock_out = Carbon::createFromFormat('H:i:s',$request->get('clock_out'))->format('h:i');
        $attendance->location = $request->get('location');
        $attendance->notes = $request->get('notes');
        $attendance->status = $request->get('status');


        $attendance->save();
        return response()->json( '', 200);

    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function show(Attendance $attendance)
    {
        return response()->json( '', 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function edit(Attendance $attendance)
    {
        return response()->json( '', 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Attendance $attendance)
    {
        return response()->json( '', 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Attendance  $attendance
     * @return \Illuminate\Http\Response
     */
    public function destroy(Attendance $attendance)
    {
        return response()->json( '', 200);
    }
}
