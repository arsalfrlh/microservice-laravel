<?php

namespace App\Jobs;

use App\Models\Video;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\SerializesModels;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class VideoUploadProccess implements ShouldQueue
{
    use Queueable, Dispatchable, SerializesModels;
    protected $videoId;

    /**
     * Create a new job instance.
     */
    public function __construct($videoId)
    {
        $this->videoId = $videoId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $video = Video::find($this->videoId);
        if(!$video){
            return;
        }

        // $connection = app('rabbitmq.connection');
        $connection = new AMQPStreamConnection(
            '127.0.0.1', // host
            5672,        // port
            'guest',     // user
            'guest'      // password
        );
        $channel = $connection->channel();

        $input = storage_path('app/public/' . $video->video_raw_path);
        
        $filename = pathinfo($video->video_raw_path, PATHINFO_FILENAME);
        $output = storage_path('app/public/video/hls' . $filename);

        //clean code
        // $inputPath = Storage::disk('public')->path($video->video_raw_path);
        // // 🔥 Generate folder output
        // $filename = pathinfo($video->video_raw_path, PATHINFO_FILENAME);
        // $outputPath = Storage::disk('public')->path("video/hls/{$filename}");

        $message = new AMQPMessage(json_encode([
            'path' => $input,
            'output' => $output
        ]));

        $channel->basic_publish($message, '', 'video');

        $channel->close();
        $connection->close();
    }
}
