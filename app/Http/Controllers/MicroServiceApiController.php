<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MicroServiceApiController extends Controller
{
    public function handle(Request $request){
        $referenceID = $request->reference_id;
        $data = $request->data;

        if(Str::startsWith($referenceID, 'PPOB')){
            $url = "http://127.0.0.1:8000/api/ppob";
            //aktifkan ini jika ingin testing
            // return response()->json(['message' => "Microservice Web PPOB berhasil dengan Reference ID: " . $request->reference_id, 'success' => true, 'data' => $data]);
        }else if(Str::startsWith($referenceID, 'PAYMENT')){
            $url = "http://127.0.0.1:8000/api/payment";
            // return response()->json(['message' => "Microservice Web PAYMENT berhasil dengan Reference ID: " . $request->reference_id, 'success' => true, 'data' => $data]);
        }else if(Str::startsWith($referenceID, ['TOPUP', 'PULSA', 'DATA'])){
            $url = "http://127.0.0.1:8000/api/micro";
            // return response()->json(['message' => "Microservice Web MICRO berhasil dengan Reference ID: " . $request->reference_id, 'success' => true, 'data' => $data]);
        }else{
            return response()->json(['message' => "Reference ID tidak Sesuai", 'success' => false]);
        }

        //atau bisa gunakan seperti ini juga jika native
        // if (str_starts_with($referenceId, 'PPOB')) {
        //     $url = 'https://ppob.example.com/api/webhook';
        // } elseif (str_starts_with($referenceId, 'PAYMENT')) {
        //     $url = 'https://payment.example.com/api/webhook';
        // } else {
        //     return response()->json(['error' => 'Unknown reference_id'], 400);
        // }

        //NOTE karena disini saya menggunakan 1 local web saja maka akan error untuk kirim requestnya| nonaktifkan jika ingin test
        try{
            $response = Http::post($url,[
                'data' => $data,
                'reference_id' => $referenceID,
            ]);
            if($response->successful()){
                return response()->json(['message' => "Data Microservice berhasil dikirim", 'success' => true, 'data' => $response->json()]);
            }else{
                return response()->json(['message' => $response->json(), 'success' => false]);
            }
        }catch(Exception $e){
            return response()->json(['message' => $e->getMessage(), 'success' => false]);
        }
    }

    //contoh web lain microservice yg memerima request kita
    public function ppob(Request $request){
        $data = $request->all();
        return response()->json(['message' => "Microservice Web PPOB berhasil dengan Reference ID: " . $request->reference_id, 'success' => true, 'data' => $data]);
    }

    public function payment(Request $request){
        $data = $request->all();
        return response()->json(['message' => "Microservice Web PAYMENT berhasil dengan Reference ID: " . $request->reference_id, 'success' => true, 'data' => $data]);
    }

    public function micro(Request $request){
        $data = $request->all();
        return response()->json(['message' => "Microservice Web MICRO berhasil dengan Reference ID: " . $request->reference_id, 'success' => true, 'data' => $data]);
    }
}
