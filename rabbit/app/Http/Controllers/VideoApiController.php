<?php

namespace App\Http\Controllers;

use App\Jobs\VideoUploadProccess;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VideoApiController extends Controller
{
    public function index(){
        $data = Video::all();
        return response()->json(['message' => "Menampilkan data video", 'success' => true, 'data' => $data], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'video' => 'required|mimes:mp4,mkv,avi'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 422);
        }

        $video = $request->file('video');
        $nmvideo = "video_" . time() . '.' . $video->getClientOriginalExtension();
        $videoPath = $video->storeAs('video/raw', $nmvideo, 'public');

        $data = Video::create([
            'video_raw_path' => $videoPath
        ]);
        VideoUploadProccess::dispatch($data->id);

        return response()->json(['message' => "Video berhasil di upload", 'success' => true], 201);
    }

    public function done(Request $request){
        $input = $request->input;
        $output = $request->output;

        // cari video berdasarkan path
        $video = Video::where('video_raw_path', 'LIKE', '%' . basename($input))->first();

        if($video){
            $video->update([
                'video_hls_path' => $output
            ]);
        }

        return response()->json([
            'message' => 'HLS selesai'
        ]);
    }
}
