<?php

namespace App\Http\Controllers;

use App\Models\Song;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class SongApiController extends Controller
{
    public function index(){
        //contoh akses lagu hlsnya "http://localhost:8000/hls/song_1/index.m3u8"
        $data = Song::all();
        return response()->json(['message' => "Menampilkan semua song", 'success' => true, 'data' => $data], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'title' => 'required',
            'song' => 'required|mimes:mp3'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 422);
        }

        $song = $request->file('song');
        $nmsong = "song_" . time() . '.' . $song->getClientOriginalExtension();
        $songPath = $song->storeAs('songs',$nmsong,'public');

        $data = Song::create([
            'title' => $request->title,
            'song_path' => $songPath
        ]);

        $connection = new AMQPStreamConnection( //buat connection ke rabbitmq
            "rabbitmq",
            5672,
            "guest",
            "guest"
        );
        $channel = $connection->channel(); //connect ke channel
        $channel->queue_declare('speech_to_text', false, true, false, false); //nama workernya itu "speech_to_text"| set durable jadi true
        $message = new AMQPMessage(json_encode([ //kirim message broker rabbitmqnya dengan isi seperti ini untuk diterima oleh worker py
            "id" => $data->id,
            "song_path" => "/app/storage/public/" . $songPath //kirim path lokasi musik yg di uploadnya
        ]));

        $channel->basic_publish($message,'','speech_to_text'); //nama workernya speech_to_text
        $channel->close(); //close connection dan channel
        $connection->close();

        return response()->json(['message' => "Song berhasil diupload", 'success' => true, 'data' => $data], 201);
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(),[
            'lyric' => 'required',
            'duration' => 'required|numeric',
            'song_path' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 422);
        }

        $song = Song::findOrFail($id);
        $song->update([
            'lyric' => $request->lyric,
            'duration' => $request->duration,
            'song_path' => $request->song_path
        ]);

        return response()->json(['message' => "Song berhasil diupdate", 'success' => true, 'data' => $song], 200);
    }
}
