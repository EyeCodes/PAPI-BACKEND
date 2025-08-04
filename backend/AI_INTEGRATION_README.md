# AI Financial Advisor Integration

This document describes the AI integration copied from the Loyalty System to the Backend project, focusing on memory, AI services, and user assignments.

## Overview

The AI integration provides intelligent financial advisory services with the following key features:

- **Natural Language Processing**: Users can interact with the AI using natural language
- **Memory System**: Persistent user memory for personalized experiences
- **Purchase Tracking**: Automatic extraction and categorization of purchases
- **Financial Insights**: AI-generated insights and recommendations
- **Multi-Provider Support**: Support for multiple AI providers (OpenAI, Anthropic, Gemini, etc.)

## Architecture

### Core Components

1. **FinancialAdvisorService** (`app/Services/FinancialAdvisorService.php`)
   - Main AI processing service
   - Handles natural language messages
   - Manages purchase extraction and categorization
   - Provides financial insights and recommendations

2. **FinancialAdvisorConfigService** (`app/Services/FinancialAdvisorConfigService.php`)
   - Manages AI provider configuration
   - Handles provider switching
   - Provides configuration status and testing

3. **UserMemory Model** (`app/Models/UserMemory.php`)
   - Stores user conversation memories
   - Tracks user preferences and insights
   - Manages memory importance and retention

4. **Purchase Model** (`app/Models/Purchase.php`)
   - Tracks user purchases
   - Supports categorization and analysis
   - Integrates with loyalty points system

5. **FinancialCategory Model** (`app/Models/FinancialCategory.php`)
   - Manages expense categories
   - Supports AI categorization
   - Provides spending analytics

## API Endpoints

### AI Processing
```
POST /api/ai/process-message
```
Process natural language messages and get AI responses with insights.

**Request:**
```json
{
    "message": "I spent 500 pesos at Jollibee yesterday"
}
```

**Response:**
```json
{
    "success": true,
    "data": {
        "message": "I've recorded your purchase at Jollibee for 500 pesos.",
        "advice": "Consider tracking your fast food spending to stay within budget.",
        "insights": [
            "Total spending: 500",
            "Category: Food & Dining"
        ],
        "recommendations": [
            "Set a monthly budget for dining out",
            "Consider meal planning to reduce expenses"
        ],
        "purchases_added": [
            {
                "id": 1,
                "merchant": "Jollibee",
                "amount": 500,
                "category": "Food & Dining"
            }
        ]
    }
}
```

### Configuration Management
```
GET /api/ai/configuration
```
Get current AI configuration and available providers.

```
POST /api/ai/change-provider
```
Change AI provider and model.

```
POST /api/ai/test-provider
```
Test current provider connection.

### Categories
```
GET /api/ai/categories
```
Get available financial categories.

## Configuration

### Environment Variables

Add these to your `.env` file:

```env
# AI Provider Configuration
FINANCIAL_ADVISOR_PROVIDER=openai
FINANCIAL_ADVISOR_MODEL=gpt-4o

# Service Configuration
FINANCIAL_ADVISOR_MAX_STEPS=10
FINANCIAL_ADVISOR_TEMPERATURE=0.7
FINANCIAL_ADVISOR_MAX_TOKENS=2000
FINANCIAL_ADVISOR_ENABLE_FUNCTION_CALLING=true

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
```

### Supported AI Providers

1. **OpenAI**
   - Models: gpt-4o, gpt-4o-mini, gpt-4-turbo, gpt-3.5-turbo
   - Default: gpt-4o

2. **Anthropic**
   - Models: claude-3-5-sonnet, claude-3-5-haiku, claude-3-opus, claude-3-sonnet
   - Default: claude-3-5-sonnet

3. **Google Gemini**
   - Models: gemini-1.5-pro, gemini-1.5-flash, gemini-pro
   - Default: gemini-1.5-pro

4. **Ollama** (Local)
   - Models: llama3.1, llama3.1-instruct, mistral, codellama
   - Default: llama3.1:8b-instruct

5. **Mistral AI**
   - Models: mistral-large, mistral-medium, mistral-small, mistral-7b
   - Default: mistral-large-latest

6. **Groq**
   - Models: llama3.1-8b, llama3.1-70b, mixtral-8x7b, gemma2-9b
   - Default: llama3.1-8b-8192

## Database Setup

### Run Migrations
```bash
php artisan migrate
```

### Seed Default Data
```bash
php artisan db:seed --class=FinancialCategorySeeder
```

### Database Tables

1. **user_memories**
   - Stores user conversation memories
   - Tracks importance and access patterns
   - Supports metadata for rich context

2. **purchases**
   - Tracks user purchases
   - Supports categorization and merchant tracking
   - Integrates with loyalty system

3. **financial_categories**
   - Manages expense categories
   - Supports AI categorization
   - Provides spending analytics

## Memory System

### Memory Types

1. **conversation**: Chat history and interactions
2. **preference**: User preferences and settings
3. **insight**: Financial insights and patterns
4. **goal**: User financial goals and targets

### Memory Features

- **Importance Scoring**: 1-10 scale for memory importance
- **Retention Management**: Automatic cleanup of old memories
- **Context Retrieval**: Smart retrieval based on relevance
- **Metadata Support**: Rich context storage

### Memory Usage

```php
// Store important memory
$userMemory = UserMemory::create([
    'user_id' => $user->id,
    'type' => 'insight',
    'content' => 'User prefers budget-friendly restaurants',
    'importance' => 7,
    'last_accessed_at' => now(),
]);

// Retrieve relevant memories
$memories = UserMemory::where('user_id', $user->id)
    ->where('importance', '>=', 5)
    ->orderBy('importance', 'desc')
    ->limit(5)
    ->get();
```

## AI Features

### Purchase Extraction

The AI can automatically extract purchase information from natural language:

```
"I spent 500 pesos at Jollibee yesterday"
→ Extracts: merchant="Jollibee", amount=500, date=yesterday
```

### Categorization

Automatic categorization of purchases using AI:

- Food & Dining
- Shopping
- Transportation
- Entertainment
- Healthcare
- Utilities
- Education
- Travel
- Other

### Financial Insights

AI-generated insights based on spending patterns:

- Spending trends
- Category analysis
- Budget recommendations
- Financial advice

### Function Calling

The AI can use tools to perform actions:

- Save purchases
- Get user points
- List available merchants
- Retrieve purchase history

## Integration with Loyalty System

The AI integration is designed to work seamlessly with the existing loyalty system:

1. **Purchase Tracking**: Purchases are automatically categorized and stored
2. **Points Integration**: AI can check and manage loyalty points
3. **Merchant Support**: Integration with merchant point systems
4. **User Context**: Leverages user profile and preferences

## Performance Optimization

### Memory Management

- Configurable memory limits
- Automatic cleanup of old memories
- Importance-based retention
- Efficient querying with indexes

### AI Provider Optimization

- Fallback mechanisms for provider failures
- Configurable timeouts and retries
- Caching of provider responses
- Efficient context building

### Database Optimization

- Indexed queries for fast retrieval
- Efficient memory storage
- Optimized purchase tracking
- Smart categorization caching

## Security Considerations

1. **User Isolation**: Memories and purchases are user-specific
2. **Data Privacy**: Sensitive information is not stored in memories
3. **Input Validation**: All user inputs are validated
4. **Error Handling**: Graceful handling of AI provider failures

## Monitoring and Logging

The system includes comprehensive logging:

- AI provider interactions
- Memory operations
- Purchase tracking
- Error handling
- Performance metrics

## Troubleshooting

### Common Issues

1. **AI Provider Connection Failed**
   - Check API keys and configuration
   - Verify network connectivity
   - Test provider with `/api/ai/test-provider`

2. **Memory Not Working**
   - Check database migrations
   - Verify user authentication
   - Check memory configuration

3. **Purchase Extraction Failing**
   - Verify AI provider is working
   - Check categorization configuration
   - Review fallback mechanisms

### Debug Commands

```bash
# Test AI provider
php artisan tinker
>>> app(\App\Services\FinancialAdvisorConfigService::class)->testProvider()

# Check memory system
php artisan tinker
>>> \App\Models\UserMemory::count()

# Verify categories
php artisan tinker
>>> \App\Models\FinancialCategory::count()
```

## Future Enhancements

1. **Advanced Analytics**: Deep spending pattern analysis
2. **Goal Tracking**: Financial goal setting and monitoring
3. **Budget Management**: Automated budget creation and tracking
4. **Predictive Insights**: AI-powered spending predictions
5. **Multi-language Support**: Support for multiple languages
6. **Voice Integration**: Voice-based interactions
7. **Mobile Optimization**: Enhanced mobile experience

## Conclusion

The AI integration provides a powerful, efficient, and user-friendly financial advisory system that seamlessly integrates with your existing loyalty system. The modular design allows for easy customization and extension while maintaining high performance and reliability. 
