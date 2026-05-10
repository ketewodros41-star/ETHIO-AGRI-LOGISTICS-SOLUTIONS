"""Tera Harvest AI Microservice — FastAPI entry point."""

from contextlib import asynccontextmanager

from dotenv import load_dotenv
from fastapi import FastAPI

from routers import agents

load_dotenv()


@asynccontextmanager
async def lifespan(app: FastAPI):
    """Startup / shutdown lifecycle."""
    yield


app = FastAPI(
    title="Tera Harvest AI Service",
    description="LangGraph agents for the Ethiopian agricultural supply chain platform.",
    version="1.0.0",
    lifespan=lifespan,
)

app.include_router(agents.router, prefix="/agents", tags=["agents"])


@app.get("/health")
async def health() -> dict:
    return {"status": "ok", "service": "tera-harvest-ai"}
