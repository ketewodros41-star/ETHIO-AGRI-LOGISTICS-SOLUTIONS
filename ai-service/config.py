"""Centralised configuration — loaded from .env."""

import os

ANTHROPIC_API_KEY: str = os.environ.get("ANTHROPIC_API_KEY", "")
OPENROUTER_API_KEY: str = os.environ.get("OPENROUTER_API_KEY", "")
LANGFUSE_PUBLIC_KEY: str = os.environ.get("LANGFUSE_PUBLIC_KEY", "")
LANGFUSE_SECRET_KEY: str = os.environ.get("LANGFUSE_SECRET_KEY", "")
LANGFUSE_HOST: str = os.environ.get("LANGFUSE_HOST", "https://cloud.langfuse.com")

LARAVEL_API_URL: str = os.environ.get("LARAVEL_API_URL", "http://api:8000/api/v1")
LARAVEL_API_TOKEN: str = os.environ.get("LARAVEL_API_TOKEN", "")

CLAUDE_MODEL: str = "claude-sonnet-4-6"
OPENROUTER_MODEL: str = "anthropic/claude-3.5-sonnet"

OPEN_METEO_URL: str = "https://api.open-meteo.com/v1/forecast"
