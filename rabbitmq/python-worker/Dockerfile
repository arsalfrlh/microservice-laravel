FROM python:3.10-slim

WORKDIR /app

RUN apt-get update && apt-get install -y ffmpeg

COPY requirements.txt .
# RUN pip install --no-cache-dir -r requirements.txt
RUN pip install --upgrade pip
# RUN pip install --default-timeout=1000 -r requirements.txt

# COPY worker.py . #ini hanya untuk akses 1 file saya yaitu worker.py
#jika seperti ini akses semua file seperti api.py, worker.py, dll
COPY . .

CMD ["sh", "-c", "python worker.py & uvicorn api:app --host 0.0.0.0 --port 5000"]