Platforms Overview
==================

This document provides a comprehensive overview of all supported AI platforms, their available models, and capabilities.

Platform Support Matrix
-----------------------

The following table shows which capabilities are supported by each platform:

.. list-table:: **Platform Capabilities**
   :header-rows: 1
   :widths: 20 8 10 10 10 10 10 10 10 10 10 10
   :class: platform-matrix

   * - Platform
     - Type
     - Chat
     - Embeddings
     - Images
     - Audio
     - PDF
     - Streaming
     - Tools
     - TTS
     - STT
     - Structured
   * - **OpenAI**
     - Remote
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Anthropic**
     - Remote
     - ✅
     - ❌
     - ✅
     - ❌
     - ❌
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
   * - **Google Gemini**
     - Remote
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **AWS Bedrock**
     - Remote
     - ✅
     - ❌
     - ✅
     - ❌
     - ❌
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
   * - **Azure OpenAI**
     - Remote
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Mistral AI**
     - Remote
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **Ollama**
     - Local
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **Voyage AI**
     - Remote
     - ❌
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
   * - **ElevenLabs**
     - Remote
     - ❌
     - ❌
     - ❌
     - ✅
     - ❌
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
   * - **Cerebras**
     - Remote
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
   * - **HuggingFace**
     - Remote
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ✅
     - ❌
     - ❌
     - ✅
     - ❌
   * - **Replicate**
     - Remote
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
   * - **LM Studio**
     - Local
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
   * - **OpenRouter**
     - Remote
     - ✅
     - ❌
     - ✅
     - ❌
     - ❌
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
   * - **Vertex AI**
     - Remote
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **Albert**
     - Remote
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
   * - **TransformersPhp**
     - Local
     - ✅
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌

Model Availability by Platform
-------------------------------

OpenAI Models
~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 30 15 15 15 15 10

   * - Model
     - Chat
     - Images In
     - Audio In
     - Tools
     - Structured
   * - **GPT-4o** (gpt-4o)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - **GPT-4o mini** (gpt-4o-mini)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - **GPT-4o audio** (gpt-4o-audio-preview)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - **GPT-4 Turbo** (gpt-4-turbo)
     - ✅
     - ✅
     - ❌
     - ✅
     - ❌
   * - **GPT-4** (gpt-4)
     - ✅
     - ❌
     - ❌
     - ✅
     - ❌
   * - **GPT-3.5 Turbo** (gpt-3.5-turbo)
     - ✅
     - ❌
     - ❌
     - ✅
     - ❌
   * - **O1 Preview** (o1-preview)
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **O1 Mini** (o1-mini)
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **O3 Mini** (o3-mini)
     - ✅
     - ✅
     - ❌
     - ❌
     - ✅
   * - **DALL-E 3**
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
   * - **DALL-E 2**
     - ❌
     - ❌
     - ❌
     - ❌
     - ❌
   * - **Whisper-1**
     - ❌
     - ❌
     - ✅
     - ❌
     - ❌

OpenAI Embeddings
~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 40 20 20 20

   * - Model
     - Dimensions
     - Max Tokens
     - Batch Support
   * - **text-embedding-3-large**
     - 3072
     - 8191
     - ✅
   * - **text-embedding-3-small**
     - 1536
     - 8191
     - ✅
   * - **text-embedding-ada-002**
     - 1536
     - 8191
     - ✅

Anthropic Claude Models
~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 35 15 15 15 20

   * - Model
     - Chat
     - Images In
     - Tools
     - Context Window
   * - **Claude 3.5 Haiku** (latest)
     - ✅
     - ✅
     - ✅
     - 200K
   * - **Claude 3.5 Sonnet** (latest)
     - ✅
     - ✅
     - ✅
     - 200K
   * - **Claude 3.7 Sonnet** (latest)
     - ✅
     - ✅
     - ✅
     - 200K
   * - **Claude 4 Sonnet**
     - ✅
     - ✅
     - ✅
     - 200K
   * - **Claude 3 Opus**
     - ✅
     - ✅
     - ✅
     - 200K
   * - **Claude 4 Opus**
     - ✅
     - ✅
     - ✅
     - 200K
   * - **Claude 4.1 Opus**
     - ✅
     - ✅
     - ✅
     - 200K

Google Gemini Models
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 30 10 10 10 10 10 10 10

   * - Model
     - Chat
     - Images
     - Audio
     - PDF
     - Tools
     - Structured
     - Server Tools
   * - **Gemini 2.0 Flash**
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - **Gemini 2.0 Pro** (exp)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
   * - **Gemini 2.0 Flash Lite** (preview)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
   * - **Gemini 2.0 Flash Thinking** (exp)
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
   * - **Gemini 1.5 Flash**
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅
     - ✅

Google Gemini Embeddings
~~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 40 20 20 20

   * - Model
     - Dimensions
     - Max Tokens
     - Batch Support
   * - **gemini-embedding-exp-03-07**
     - 768
     - Variable
     - ✅
   * - **text-embedding-004**
     - 768
     - Variable
     - ✅
   * - **embedding-001**
     - 768
     - Variable
     - ✅

Mistral AI Models
~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 35 15 15 15 20

   * - Model
     - Chat
     - Images In
     - Tools
     - Structured
   * - **Codestral** (latest)
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Mistral Large** (latest)
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Mistral Small** (latest)
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Mistral Nemo**
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Pixstral Large** (latest)
     - ✅
     - ✅
     - ✅
     - ✅
   * - **Pixstral 12B** (latest)
     - ✅
     - ✅
     - ✅
     - ✅
   * - **Mistral Embed**
     - ❌
     - ❌
     - ❌
     - ❌

AWS Bedrock Models
~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 35 15 15 15 20

   * - Model Family
     - Chat
     - Images In
     - Tools
     - Streaming
   * - **Claude Models** (via Bedrock)
     - ✅
     - ✅
     - ✅
     - ✅
   * - **Llama Models** (via Bedrock)
     - ✅
     - ✅
     - ❌
     - ✅
   * - **Nova Micro**
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Nova Lite**
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Nova Pro**
     - ✅
     - ❌
     - ✅
     - ✅
   * - **Nova Premier**
     - ✅
     - ❌
     - ✅
     - ✅

Ollama Models
~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 30 15 15 15 15 10

   * - Model
     - Chat
     - Images In
     - Tools
     - Structured
     - Embeddings
   * - **DeepSeek R1**
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
   * - **Llama 3.2**
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
   * - **Llama 3.1**
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
   * - **Mistral**
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
   * - **Qwen 2.5**
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
   * - **Qwen 2.5 VL**
     - ✅
     - ✅
     - ✅
     - ✅
     - ❌
   * - **Gemma 3**
     - ✅
     - ❌
     - ✅
     - ✅
     - ❌
   * - **LLaVA**
     - ✅
     - ✅
     - ❌
     - ❌
     - ❌
   * - **nomic-embed-text**
     - ❌
     - ❌
     - ❌
     - ❌
     - ✅
   * - **bge-m3**
     - ❌
     - ❌
     - ❌
     - ❌
     - ✅
   * - **all-minilm**
     - ❌
     - ❌
     - ❌
     - ❌
     - ✅

Voyage AI Embeddings
~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 40 20 20 20

   * - Model
     - Dimensions
     - Context Length
     - Specialized For
   * - **voyage-3.5**
     - 1024
     - 32K
     - General
   * - **voyage-3.5-lite**
     - 512
     - 16K
     - General
   * - **voyage-3**
     - 1024
     - 16K
     - General
   * - **voyage-3-large**
     - 2048
     - 16K
     - General
   * - **voyage-finance-2**
     - 1024
     - 32K
     - Finance
   * - **voyage-multilingual-2**
     - 1024
     - 32K
     - Multilingual
   * - **voyage-law-2**
     - 1024
     - 32K
     - Legal
   * - **voyage-code-3**
     - 1024
     - 32K
     - Code
   * - **voyage-code-2**
     - 1536
     - 16K
     - Code

ElevenLabs Voice Models
~~~~~~~~~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 35 20 20 25

   * - Model
     - TTS
     - STT
     - Type
   * - **Eleven V3**
     - ✅
     - ❌
     - General TTS
   * - **Eleven Flash V2.5**
     - ✅
     - ❌
     - Fast TTS
   * - **Eleven Turbo V2.5**
     - ✅
     - ❌
     - Turbo TTS
   * - **Eleven Multilingual V2**
     - ✅
     - ❌
     - Multilingual TTS
   * - **Scribe V1**
     - ❌
     - ✅
     - Speech Recognition

Cerebras Models
~~~~~~~~~~~~~~~~

.. list-table::
   :header-rows: 1
   :widths: 40 20 20 20

   * - Model
     - Chat
     - Streaming
     - Parameters
   * - **Llama 4 Scout 17B**
     - ✅
     - ✅
     - 17B
   * - **Llama 4 Maverick 17B**
     - ✅
     - ✅
     - 17B
   * - **Llama 3.3 70B**
     - ✅
     - ✅
     - 70B
   * - **Llama 3.1 8B**
     - ✅
     - ✅
     - 8B
   * - **Qwen 3 32B**
     - ✅
     - ✅
     - 32B
   * - **Qwen 3 235B**
     - ✅
     - ✅
     - 235B
   * - **Qwen 3 Coder 480B**
     - ✅
     - ✅
     - 480B
   * - **GPT OSS 120B**
     - ✅
     - ✅
     - 120B

Capability Legend
-----------------

* **Type**: Remote (cloud-based API) or Local (self-hosted)
* **Chat**: Text generation and conversation capabilities
* **Embeddings**: Vector embeddings for semantic search and similarity
* **Images**: Image input processing (vision models)
* **Audio**: Audio input processing
* **PDF**: Direct PDF document processing
* **Streaming**: Real-time streaming responses
* **Tools**: Function/tool calling support
* **TTS**: Text-to-speech generation
* **STT**: Speech-to-text transcription
* **Structured**: Structured output/JSON mode support
* **Server Tools**: Platform-provided tools (search, code execution, etc.)

Notes
-----

* Platform availability may vary based on your API access and region
* Model capabilities are subject to change as platforms update their offerings
* Some models require specific API versions or configurations
* Batch processing support varies by platform and model
* Context window sizes and token limits vary significantly between models
* Pricing and rate limits differ across platforms and models

For detailed configuration and usage examples for each platform, please refer to the specific platform documentation.