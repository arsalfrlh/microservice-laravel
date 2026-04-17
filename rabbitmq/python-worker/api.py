from fastapi import FastAPI
from recommender import recommend_logic

app = FastAPI()

@app.get("/recommend/{user_id}")
def recommend(user_id: int):
    return recommend_logic(user_id)