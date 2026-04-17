import pika
import json
import requests
import whisper
import subprocess
import os
from sentence_transformers import SentenceTransformer

RABBITMQ_HOST = "rabbitmq" #nama service dari docker-compose

LARAVEL_API_URL = "http://laravel_app:8000/api" #isi urlnya dengan nama kontainer dari docker-compose

HLS_OUTPUT_DIR="/app/hls_output" #ini adalah folder hls jika di baca di folder| tapi dibaca hls_output di sistem karena di docker-compose di ubah
os.makedirs(HLS_OUTPUT_DIR, exist_ok=True)
model = whisper.load_model("base") #load model whisper
embed_model = SentenceTransformer('all-MiniLM-L6-v2')

def convert_to_hls(input_path, song_id):
    output_folder = f"{HLS_OUTPUT_DIR}/song_{song_id}" #contoh "hls/song_1" |di baca di sistem "hls_output/song_1"
    os.makedirs(output_folder, exist_ok=True) #buat direktori

    output_m3u8= f"{output_folder}/index.m3u8"

    cmd = [
        "ffmpeg",
        "-i", input_path,
        "-c:a", "aac",
        "-b:a", "128k",
        "-hls_time", "10",
        "-hls_list_size", "0",
        "-f", "hls",
        output_m3u8
    ]

    subprocess.run(cmd, check=True)
    return f"hls/song_{song_id}/index.m3u8" #return string path lokasi file hlsnya yg akan dikirim ke api laravel dan di save ke database

def get_duration(file_path):
    cmd = [
        "ffprobe",
        "-v", "error",
        "-show_entries", "format=duration",
        "-of", "default=noprint_wrappers=1:nokey=1",
        file_path
    ]

    result = subprocess.run(cmd, stdout=subprocess.PIPE, check=True)
    return float(result.stdout.decode().strip()) #return float durasi yg akan di kirim ke api laravel dan disimpan ke database

def speech_to_text(file_path):
    result = model.transcribe(file_path) #konvert lagu ke teks dengan AI whisper
    return result['text'] #return string lyric dan kirim ke api laravel dan simpan ke database

def callback(ch, method, properties, body): #function yg akan menerima data json dari message queue rabbitmq
    data = json.loads(body) #decode isi json

    song_id = data['id']
    file_path = data['song_path']

    try:
        print("🎬 HLS...")
        hls_path = convert_to_hls(file_path, song_id)

        print("⏱️ Duration...")
        duration = get_duration(file_path)

        print("🎤 Whisper...")
        lyric = speech_to_text(file_path)
        
        print("📤 Update Laravel...")
        requests.put(f"{LARAVEL_API_URL}/song/{song_id}", json={
            "lyric": lyric,
            "duration": duration,
            "song_path": hls_path
        })
        print("✅ DONE")

    except Exception as e:
        print(f"Error: {e}")

    ch.basic_ack(delivery_tag=method.delivery_tag)
    

def callback_video(ch, method, properties, body):
    data = json.loads(body)

    video = data['video_path']
    caption = data['caption']
    post_id = data['post_id']

    # TRANSCRIBE
    result = model.transcribe(video)
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
        "-codec", "copy",
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

connection = pika.BlockingConnection( #connect ke rabbitmq
    pika.ConnectionParameters(host=RABBITMQ_HOST)
)

channel = connection.channel() #connect ke channelnya
channel.queue_declare(queue="speech_to_text", durable=True) #worker hanya akan bekerja untuk nama worker speech_to_text
channel.queue_declare(queue="video_proccess", durable=True)

channel.basic_qos(prefetch_count=1)
channel.basic_consume(queue="speech_to_text", on_message_callback=callback) #menerima message dari rabbitmq berupa json dan di proses di function callback
channel.basic_consume(queue="video_proccess", on_message_callback=callback_video)

print("🚀 Worker running...")
channel.start_consuming()