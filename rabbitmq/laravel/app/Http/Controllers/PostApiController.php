<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Post;
use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class PostApiController extends Controller
{
    public function index(Request $request){
        $user = $request->user();
        $response = Http::get("http://python_api:5000/recommend/" . $user->id);
        $postId = $response->json();

        $posts = Post::with('user')->withExists(["likedBy as liked" => function($query) use ($user){
            $query->where('likes.user_id', $user->id);
        }])->whereIn('id',$postId)->withCount('like','view')->whereIn('id', $postId)->get()->keyBy('id');

        $data = collect($postId)->map(function ($id) use ($posts) {
            return $posts[$id] ?? null;
        })->filter()->values();

        return response()->json(['message' => "Menampilkan postingan rekomendasi", 'success' => true, 'data' => $data], 200);
    }

    public function store(Request $request){
        $validator = Validator::make($request->all(),[
            'caption' => 'required',
            'video' => 'required|mimes:mp4'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 422);
        }

        $user = $request->user();
        $video = $request->file('video');
        $nmvideo = "video_" . time() . '.' . $video->getClientOriginalExtension();
        $videoPath = $video->storeAs('videos',$nmvideo,'public');

        $data = Post::create([
            'user_id' => $user->id,
            'caption' => $request->caption,
            'video_path' => $videoPath
        ]);

        $connection = new AMQPStreamConnection(
            "rabbitmq",
            5672,
            "guest",
            "guest",
        );
        $channel = $connection->channel();
        $channel->queue_declare("video_proccess", false, true, false, false);
        $message = new AMQPMessage(json_encode([
            'video_path' => "/app/storage/public/" . $videoPath,
            'caption' => $request->caption,
            'post_id' => $data->id
        ]));

        $channel->basic_publish($message,'','video_proccess');
        $channel->close();
        $connection->close();

        return response()->json(['message' => "Postingan berhasil di upload", 'success' => true, 'data' => $data], 201);
    }

    public function update(Request $request, $id){
        $validator = Validator::make($request->all(),[
            'subtitle' => 'required',
            'embedding' => 'required',
            'video_path' => 'required'
        ]);

        if($validator->fails()){
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 422);
        }

        $data = Post::findOrFail($id);
        $data->update([
            'subtitle' => $request->subtitle,
            'embedding' => $request->embedding,
            'video_path' => $request->video_path,
        ]);

        return response()->json(['message' => "Video berhasil di proses", 'success' => true, 'data' => $data], 200);
    }

    public function aiData($id){
        return [
            'posts' => Post::all(),
            'likes' => Like::where('user_id',$id)->pluck('post_id'),
            'views' => View::where('user_id',$id)->pluck('post_id'),
        ];

        return response()->json($data);
    }
}
