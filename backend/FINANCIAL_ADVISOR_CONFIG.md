# Financial Advisor Configuration Guide

## 🎯 **Flexible AI Provider System**

The financial advisor now supports multiple AI providers and models that can be changed dynamically through configuration or API calls.

## 📋 **Environment Variables**

Add these to your `.env` file:

```env
# AI Provider Configuration
FINANCIAL_ADVISOR_PROVIDER=openai
FINANCIAL_ADVISOR_MODEL=gpt-4o

# Service Configuration
FINANCIAL_ADVISOR_MAX_STEPS=10
FINANCIAL_ADVISOR_TEMPERATURE=0.7
FINANCIAL_ADVISOR_MAX_TOKENS=2000

# Memory Configuration
FINANCIAL_ADVISOR_MAX_MEMORIES=100
FINANCIAL_ADVISOR_MEMORY_RETENTION_DAYS=365
FINANCIAL_ADVISOR_IMPORTANCE_THRESHOLD=5

# Categorization Configuration
FINANCIAL_ADVISOR_CATEGORIZATION_CONFIDENCE=0.7
FINANCIAL_ADVISOR_FALLBACK_CATEGORY=Uncategorized

# Response Configuration
FINANCIAL_ADVISOR_INCLUDE_SUMMARY=true
FINANCIAL_ADVISOR_INCLUDE_INSIGHTS=true
FINANCIAL_ADVISOR_INCLUDE_RECOMMENDATIONS=true
FINANCIAL_ADVISOR_MAX_RECOMMENDATIONS=5

# Function Calling Configuration
FINANCIAL_ADVISOR_ENABLE_FUNCTION_CALLING=true
```

## 🤖 **Supported Providers**

### **OpenAI**
```env
FINANCIAL_ADVISOR_PROVIDER=openai
FINANCIAL_ADVISOR_MODEL=gpt-4o
```

**Available Models:**
- `gpt-4o` (default)
- `gpt-4o-mini`
- `gpt-4-turbo`
- `gpt-3.5-turbo`

### **Anthropic (Claude)**
```env
FINANCIAL_ADVISOR_PROVIDER=anthropic
FINANCIAL_ADVISOR_MODEL=claude-3-5-sonnet
```

**Available Models:**
- `claude-3-5-sonnet` (default)
- `claude-3-5-haiku`
- `claude-3-opus`
- `claude-3-sonnet`

### **Google Gemini**
```env
FINANCIAL_ADVISOR_PROVIDER=gemini
FINANCIAL_ADVISOR_MODEL=gemini-1.5-pro
```

**Available Models:**
- `gemini-1.5-pro` (default)
- `gemini-1.5-flash`
- `gemini-pro`

### **Ollama (Local)**
```env
FINANCIAL_ADVISOR_PROVIDER=ollama
FINANCIAL_ADVISOR_MODEL=llama3.1:8b-instruct
```

**Available Models:**
- `llama3.1:8b-instruct` (default)
- `llama3.1:8b`
- `mistral:7b`
- `codellama:7b`

### **Mistral AI**
```env
FINANCIAL_ADVISOR_PROVIDER=mistral
FINANCIAL_ADVISOR_MODEL=mistral-large-latest
```

**Available Models:**
- `mistral-large-latest` (default)
- `mistral-medium-latest`
- `mistral-small-latest`
- `mistral-7b-instruct`

### **Groq (Ultra-Fast)**
```env
FINANCIAL_ADVISOR_PROVIDER=groq
FINANCIAL_ADVISOR_MODEL=llama3.1-8b-8192
```

**Available Models:**
- `llama3.1-8b-8192` (default)
- `llama3.1-70b-8192`
- `mixtral-8x7b-32768`
- `gemma2-9b-it`

### **xAI (Grok)**
```env
FINANCIAL_ADVISOR_PROVIDER=xai
FINANCIAL_ADVISOR_MODEL=grok-beta
```

**Available Models:**
- `grok-beta` (default)
- `grok-2`

### **DeepSeek**
```env
FINANCIAL_ADVISOR_PROVIDER=deepseek
FINANCIAL_ADVISOR_MODEL=deepseek-chat
```

**Available Models:**
- `deepseek-chat` (default)
- `deepseek-coder`
- `deepseek-llm-7b-chat`

### **Voyage AI**
```env
FINANCIAL_ADVISOR_PROVIDER=voyageai
FINANCIAL_ADVISOR_MODEL=voyage-large-2
```

**Available Models:**
- `voyage-large-2` (default)
- `voyage-code-2`
- `voyage-multilingual-2`

### **OpenRouter (Multi-Provider)**
```env
FINANCIAL_ADVISOR_PROVIDER=openrouter
FINANCIAL_ADVISOR_MODEL=openai/gpt-4o
```

**Available Models:**
- `openai/gpt-4o` (default)
- `openai/gpt-4o-mini`
- `anthropic/claude-3-5-sonnet`
- `anthropic/claude-3-5-haiku`
- `google/gemini-1.5-pro`
- `meta-llama/llama-3.1-8b-instruct`

## 🔧 **API Configuration Management**

### **Get Current Configuration**
```bash
GET /api/financial-advisor/configuration
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "status": {
            "current_provider": "openai",
            "current_model": "gpt-4o",
            "provider_name": "OpenAI",
            "available_models": {...},
            "configuration": {...},
            "features": {...}
        },
        "available_providers": {...},
        "config_summary": {...}
    }
}
```

### **Change Provider**
```bash
POST /api/financial-advisor/change-provider
Authorization: Bearer {token}
Content-Type: application/json

{
    "provider": "anthropic",
    "model": "claude-3-5-sonnet"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Provider changed successfully",
    "data": {
        "current_provider": "anthropic",
        "current_model": "claude-3-5-sonnet",
        "provider_name": "Anthropic",
        ...
    }
}
```

### **Test Provider Connection**
```bash
POST /api/financial-advisor/test-provider
Authorization: Bearer {token}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "success": true,
        "provider": "openai",
        "model": "gpt-4o",
        "response": "Hello! I can read this message.",
        "timestamp": "2025-07-27T10:30:00.000000Z"
    }
}
```

## 🚀 **Quick Provider Switching Examples**

### **Switch to Claude for Better Reasoning**
```bash
curl -X POST "https://your-api.com/api/financial-advisor/change-provider" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"provider": "anthropic", "model": "claude-3-5-sonnet"}'
```

### **Switch to Gemini for Cost Efficiency**
```bash
curl -X POST "https://your-api.com/api/financial-advisor/change-provider" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"provider": "gemini", "model": "gemini-1.5-flash"}'
```

### **Switch to Local Ollama for Privacy**
```bash
curl -X POST "https://your-api.com/api/financial-advisor/change-provider" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"provider": "ollama", "model": "llama3.1:8b-instruct"}'
```

## ⚙️ **Configuration Features**

### **Dynamic Temperature Control**
```env
FINANCIAL_ADVISOR_TEMPERATURE=0.3  # More focused responses
FINANCIAL_ADVISOR_TEMPERATURE=0.9  # More creative responses
```

### **Token Management**
```env
FINANCIAL_ADVISOR_MAX_TOKENS=1000  # Shorter responses
FINANCIAL_ADVISOR_MAX_TOKENS=4000  # Longer, detailed responses
```

### **Memory Management**
```env
FINANCIAL_ADVISOR_MAX_MEMORIES=50      # Limit memory storage
FINANCIAL_ADVISOR_MAX_MEMORIES=200     # More memory capacity
```

### **Response Customization**
```env
FINANCIAL_ADVISOR_INCLUDE_SUMMARY=false      # Disable summary
FINANCIAL_ADVISOR_INCLUDE_INSIGHTS=false     # Disable insights
FINANCIAL_ADVISOR_INCLUDE_RECOMMENDATIONS=false  # Disable recommendations
```

## 🔄 **Programmatic Configuration**

You can also change providers programmatically:

```php
use App\Services\FinancialAdvisorConfigService;

$configService = new FinancialAdvisorConfigService();

// Change to Claude
$configService->changeProvider('anthropic', 'claude-3-5-sonnet');

// Get current status
$status = $configService->getProviderStatus();

// Test connection
$test = $configService->testProvider();
```

## 📊 **Provider Comparison**

| Provider | Best For | Speed | Cost | Features |
|----------|----------|-------|------|----------|
| **OpenAI GPT-4o** | General use, coding | Fast | Medium | Excellent |
| **Anthropic Claude** | Reasoning, analysis | Medium | Medium | Very Good |
| **Google Gemini** | Cost efficiency | Fast | Low | Good |
| **Mistral AI** | Multilingual, reasoning | Fast | Low | Very Good |
| **Groq** | Ultra-fast inference | Very Fast | Low | Good |
| **xAI (Grok)** | Real-time data, humor | Fast | Medium | Good |
| **DeepSeek** | Coding, reasoning | Fast | Low | Good |
| **Voyage AI** | Multilingual, embeddings | Fast | Low | Good |
| **OpenRouter** | Multi-provider access | Variable | Variable | Excellent |
| **Ollama** | Privacy, local | Slow | Free | Basic |

## 🎯 **Use Cases**

### **High-Volume Processing**
```env
FINANCIAL_ADVISOR_PROVIDER=gemini
FINANCIAL_ADVISOR_MODEL=gemini-1.5-flash
FINANCIAL_ADVISOR_TEMPERATURE=0.3
FINANCIAL_ADVISOR_MAX_TOKENS=1000
```

### **Complex Financial Analysis**
```env
FINANCIAL_ADVISOR_PROVIDER=anthropic
FINANCIAL_ADVISOR_MODEL=claude-3-5-sonnet
FINANCIAL_ADVISOR_TEMPERATURE=0.1
FINANCIAL_ADVISOR_MAX_TOKENS=3000
```

### **Privacy-First Environment**
```env
FINANCIAL_ADVISOR_PROVIDER=ollama
FINANCIAL_ADVISOR_MODEL=llama3.1:8b-instruct
FINANCIAL_ADVISOR_TEMPERATURE=0.7
FINANCIAL_ADVISOR_MAX_TOKENS=2000
```

### **Ultra-Fast Processing (Groq)**
```env
FINANCIAL_ADVISOR_PROVIDER=groq
FINANCIAL_ADVISOR_MODEL=llama3.1-8b-8192
FINANCIAL_ADVISOR_TEMPERATURE=0.3
FINANCIAL_ADVISOR_MAX_TOKENS=1500
```

### **Multilingual Support (Mistral)**
```env
FINANCIAL_ADVISOR_PROVIDER=mistral
FINANCIAL_ADVISOR_MODEL=mistral-large-latest
FINANCIAL_ADVISOR_TEMPERATURE=0.5
FINANCIAL_ADVISOR_MAX_TOKENS=2500
```

### **Real-Time Data (xAI Grok)**
```env
FINANCIAL_ADVISOR_PROVIDER=xai
FINANCIAL_ADVISOR_MODEL=grok-beta
FINANCIAL_ADVISOR_TEMPERATURE=0.8
FINANCIAL_ADVISOR_MAX_TOKENS=3000
```

### **Multi-Provider Access (OpenRouter)**
```env
FINANCIAL_ADVISOR_PROVIDER=openrouter
FINANCIAL_ADVISOR_MODEL=anthropic/claude-3-5-sonnet
FINANCIAL_ADVISOR_TEMPERATURE=0.4
FINANCIAL_ADVISOR_MAX_TOKENS=2000
```

## 🔧 **Troubleshooting Function Calling Issues**

Some models may not support function calling properly. If you encounter errors like:
```
"Failed to call a function. Please adjust your prompt."
```

### **Solutions:**

1. **Disable Function Calling:**
```env
FINANCIAL_ADVISOR_ENABLE_FUNCTION_CALLING=false
```

2. **Use a Different Model:**
```env
FINANCIAL_ADVISOR_PROVIDER=openai
FINANCIAL_ADVISOR_MODEL=gpt-4o
```

3. **Test Models:**
```bash
# Test all models
php artisan financial-advisor:test-models

# Test specific provider
php artisan financial-advisor:test-models --provider=openai

# Test specific model
php artisan financial-advisor:test-models --provider=openai --model=gpt-4o

# Test with custom message
php artisan financial-advisor:test-models --message="I bought groceries for 500 pesos"
```

### **Models Known to Work Well:**
- ✅ **OpenAI GPT-4o** - Excellent function calling support
- ✅ **Anthropic Claude 3.5 Sonnet** - Good function calling support
- ✅ **Google Gemini 1.5 Pro** - Good function calling support
- ⚠️ **OpenRouter models** - Variable support (test first)
- ❌ **Some local models** - May not support function calling

### **Fallback System:**
When function calling fails, the system automatically falls back to:
- **Regex-based purchase extraction**
- **Basic categorization**
- **Simple advice generation**
- **Memory storage**

This ensures the system always works, even with problematic models.

The system is now fully flexible and can adapt to your specific needs! 🚀 
