<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookPlace;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try{
            return response()->json([
                'data'=>Booking::where('type','static')->get(),
            ],200);
        }catch(Exception $e){
            return response()->json([
                'message'=>$e->getMessage(),
            ],404);

        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store_Admin(Request $request)
    {
        $date=Carbon::now()->format('Y-m-d');
        $validatedData =Validator::make($request->all(),[
            'source_trip_id'=>'required|exists:countries,id',
            'destination_trip_id'=>'required|exists:countries,id',
            'trip_name'=>'required|string',
            'price'=>'required|numeric',
            'number_of_people'=>'required|min:1|numeric',
            'start_date'=>"required|date|unique:bookings,start_date|after_or_equal:$date",
            'end_date'=>'required|date|after_or_equal:end_date',
            'trip_note'=>'required|string',
       ]);
           if( $validatedData->fails() ){
               return response()->json([
                   'message'=> $validatedData->errors()->first(),
               ],422);
           }
           try{
           $booking=Booking::create([
            'user_id'=>auth()->user()->id,
            'source_trip_id'=>$request->source_trip_id,
            'destination_trip_id'=>$request->destination_trip_id,
            'trip_name'=>$request->trip_name,
            'price'=>$request->price,
            'number_of_people'=>$request->number_of_people,
            'start_date'=>$request->start_date,
            'end_date'=>$request->end_date,
            'trip_note'=>$request->trip_note,
            'type'=>'static'
        
        ]);            
        }catch(Exception $exception){
            return response()->json([
                'message'=>$exception->getMessage(),
            ]);

        }
        $book_place=BookPlace::create([
            
        ]);
        return response()->json([
            'data'=>$booking
        ],200);
    }



    public function store_User(Request $request)
    {
       // $date=Carbon::now()->format('Y-m-d');
        $validatedData =Validator::make($request->all(),[
            'source_trip_id'=>'required|exists:countries,id',
            'destination_trip_id'=>'required|exists:countries,id',
            'trip_name'=>'required|string',
            'number_of_people'=>'required|min:1|numeric',
            'trip_note'=>'required|string',
       ]);
           if( $validatedData->fails() ){
               return response()->json([
                   'message'=> $validatedData->errors()->first(),
               ],422);
           }
           try{
           $booking=Booking::create([
            'user_id'=>auth()->user()->id,
            'source_trip_id'=>$request->source_trip_id,
            'destination_trip_id'=>$request->destination_trip_id,
            'trip_name'=>$request->trip_name,
            'number_of_people'=>$request->number_of_people,
            'trip_note'=>$request->trip_note,
            'type'=>'dynamic'
        
        ]);            
        }catch(Exception $exception){
            return response()->json([
                'message'=>$exception->getMessage(),
            ]);

        }
        return response()->json([
            'data'=>$booking
        ],200);
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try{
            $booking=Booking::findOrFail($id);
            return response()->json([
                'data'=>$booking
            ],200);

        }catch(Exception $e){
            return response()->json([
                'message'=>'Not Found'
            ],404);

        }
    }
    public function update_Admin(Request $request,$id)
    {
        try{
            $booking= Booking::findOrFail($id);
            if(auth()->id() != $booking->user_id)
            {
                return response()->json([
                    'message'=>'You do not have the permission'
                ],200);
            }
        }catch(\Exception $e){
            return response()->json([
                'message'=> 'Not found',
            ],404);
        }
        $date=Carbon::now()->format('Y-m-d');
        $validator = Validator::make($request->all(), [
            'source_trip_id'=>'required|exists:countries,id',
            'destination_trip_id'=>'required|exists:countries,id',
            'trip_name'=>'required|string',
            'price'=>'required|numeric',
            'number_of_people'=>'required|min:1|numeric',
            'start_date'=>"required|date|unique:bookings,start_date|after_or_equal:$date",
            'end_date'=>'required|date|after_or_equal:end_date',
            'trip_note'=>'required|string',
          ]);

          if($validator->fails()){
              return response()->json([
                  'message'=> $validator->errors()->first(),
              ],422);
          }

          $booking->source_trip_id = $request->source_trip_id;
          $booking->destination_trip_id = $request->destination_trip_id;
          $booking->trip_name = $request->trip_name;
          $booking->price = $request->price;
          $booking->number_of_people = $request->number_of_people;
          $booking->start_date = $request->start_date;
          $booking->end_date = $request->end_date;
          $booking->trip_note = $request->trip_note;
          $booking->save();
          return response()->json([
            'message'=> 'booking has been updated successfully',
            'data'=>booking::with('country:id,name','area:id,name','user:id,name,email,image,position')
                            ->select('id','name','user_id','area_id','country_id')
                            ->where('id',$booking->id)
                            ->get(),
          ],200);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Booking $booking)
    {
        //
    }
}
