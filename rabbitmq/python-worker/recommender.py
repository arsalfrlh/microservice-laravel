import numpy as np
import requests
import json
import random

def cosine(a, b):
    a = np.array(a)
    b = np.array(b)

    norm_a = np.linalg.norm(a)
    norm_b = np.linalg.norm(b)

    if norm_a == 0 or norm_b == 0:
        return 0

    return np.dot(a, b) / (norm_a * norm_b)


def safe_embedding(e):
    if e is None:
        return None
    if isinstance(e, str):
        return json.loads(e)
    return e


def recommend_logic(user_id):

    data = requests.get(f"http://laravel_app:8000/api/ai-data/{user_id}").json()

    posts = data['posts']
    likes = data['likes']
    views = data['views']

    posts = [p for p in posts if p['embedding'] is not None]

    liked_embeddings = []
    viewed_embeddings = []

    for p in posts:
        emb = safe_embedding(p['embedding'])

        if p['id'] in likes:
            liked_embeddings.append(emb)

        if p['id'] in views:
            viewed_embeddings.append(emb)

    if not liked_embeddings and not viewed_embeddings:
        return [p['id'] for p in random.sample(posts, min(10, len(posts)))]

    vectors = []

    if liked_embeddings:
        vectors.append(np.mean(liked_embeddings, axis=0) * 0.7)

    if viewed_embeddings:
        vectors.append(np.mean(viewed_embeddings, axis=0) * 0.3)

    if not vectors:
        return [p['id'] for p in random.sample(posts, min(10, len(posts)))]

    user_vector = np.mean(vectors, axis=0)

    scored = []

    for p in posts:
        emb = safe_embedding(p['embedding'])
        sim = cosine(user_vector, emb)

        score = sim

        if p['id'] in likes:
            score += 0.3

        if p['id'] in views:
            score += 0.1

        scored.append((score, p['id']))

    scored.sort(reverse=True, key=lambda x: x[0])

    return [x[1] for x in scored[:10]]