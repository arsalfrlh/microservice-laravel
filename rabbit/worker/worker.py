import pika
import json
import subprocess
import os
import requests

def process_video(body):
    data = json.loads(body)

    if 'path' not in data or 'output' not in data:
        print("INVALID MESSAGE:", data)
        return

    input_path = data['path']
    output_dir = data['output']

    os.makedirs(output_dir, exist_ok=True)

    output_m3u8 = os.path.join(output_dir, "index.m3u8")

    command = [
        "ffmpeg",
        "-i", input_path,
        "-codec", "copy",
        "-start_number", "0",
        "-hls_time", "10",
        "-hls_list_size", "0",
        "-f", "hls",
        output_m3u8
    ]

    subprocess.run(command)

    # callback ke laravel
    requests.post("http://localhost:8000/api/video/done", json={
        "output": output_dir,
        "input": input_path
    })


def callback(ch, method, properties, body):
    print("Processing video...")
    process_video(body)
    ch.basic_ack(delivery_tag=method.delivery_tag)


connection = pika.BlockingConnection(
    pika.ConnectionParameters(
        host='localhost',
        port=5672,
        credentials=pika.PlainCredentials('guest', 'guest')
    )
)

channel = connection.channel()
channel.queue_declare(queue='video')

channel.basic_consume(queue='video', on_message_callback=callback)

print("Waiting for jobs...")
channel.start_consuming()