"""LLM factory — Claude primary, OpenRouter fallback."""

import anthropic
import config
from langchain_anthropic import ChatAnthropic
from langchain_core.language_models import BaseChatModel
from langfuse import Langfuse
from openai import OpenAI as OpenRouterClient

_langfuse = Langfuse(
    public_key=config.LANGFUSE_PUBLIC_KEY,
    secret_key=config.LANGFUSE_SECRET_KEY,
    host=config.LANGFUSE_HOST,
) if config.LANGFUSE_PUBLIC_KEY else None


def get_llm(fallback: bool = False) -> BaseChatModel:
    """Return Claude (primary) or OpenRouter (fallback)."""
    if fallback or not config.ANTHROPIC_API_KEY:
        return ChatAnthropic(
            model=config.OPENROUTER_MODEL,
            api_key=config.OPENROUTER_API_KEY,
            base_url="https://openrouter.ai/api/v1",
            temperature=0,
        )
    return ChatAnthropic(
        model=config.CLAUDE_MODEL,
        api_key=config.ANTHROPIC_API_KEY,
        temperature=0,
        max_tokens=4096,
    )


def trace(name: str, input_data: dict, output_data: dict) -> None:
    """Log a call to Langfuse for cost attribution."""
    if _langfuse:
        trace_obj = _langfuse.trace(name=name)
        trace_obj.generation(
            name=name,
            model=config.CLAUDE_MODEL,
            input=input_data,
            output=output_data,
        )
