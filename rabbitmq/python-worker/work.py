import pika, json, subprocess, requests
import whisper
import os
from sentence_transformers import SentenceTransformer

RABBITMQ_HOST = "rabbitmq"
QUEUE_NAME = "video_proccess"

LARAVEL_API_URL = "http://laravel_app:8000/api"

HLS_OUTPUT_DIR="/app/hls_output"
os.makedirs(HLS_OUTPUT_DIR, exist_ok=True)
whisper_model = whisper.load_model("base")
embed_model = SentenceTransformer('all-MiniLM-L6-v2')

def callback_video(ch, method, properties, body):
    data = json.loads(body)

    video = data['video_path']
    caption = data['caption']
    post_id = data['post_id']

    # TRANSCRIBE
    result = whisper_model.transcribe(video)
    subtitle = result["text"]

    # EMBEDDING
    text = caption + " " + subtitle
    embedding = embed_model.encode(text).tolist()

    # HLS
    output_folder = f"{HLS_OUTPUT_DIR}/video_{post_id}"
    os.makedirs(output_folder, exist_ok=True)
    output = f"{output_folder}/index.m3u8"

    subprocess.run([
        "ffmpeg",
        "-i", video,
        "-codec:", "copy",
        "-hls_time", "5",
        "-hls_list_size", "0",
        "-f", "hls",
        output
    ],check=True)

    # UPDATE LARAVEL
    requests.put(f"{LARAVEL_API_URL}/post/{post_id}", json={
        "subtitle": subtitle,
        "embedding": embedding,
        "video_path": output
    })

    print("DONE", post_id)
    ch.basic_ack(delivery_tag=method.delivery_tag)


conn = pika.BlockingConnection(pika.ConnectionParameters(host=RABBITMQ_HOST))
ch = conn.channel()
ch.basic_qos(prefetch_count=1)
ch.queue_declare(queue=QUEUE_NAME, durable=True)

ch.basic_consume(queue=QUEUE_NAME, on_message_callback=callback, auto_ack=True)

print("Worker running...")
ch.start_consuming()