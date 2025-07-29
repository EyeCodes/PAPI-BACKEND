<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AI provider and model for the financial advisor service.
    | You can easily change these settings to use different providers.
    |
    */

    'provider' => env('FINANCIAL_ADVISOR_PROVIDER', 'openai'),

    'model' => env('FINANCIAL_ADVISOR_MODEL', 'gpt-4o'),

    /*
    |--------------------------------------------------------------------------
    | Provider Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for different AI providers.
    |
    */

    'providers' => [
        'openai' => [
            'name' => 'OpenAI',
            'provider' => \Prism\Prism\Enums\Provider::OpenAI,
            'models' => [
                'gpt-4o' => 'gpt-4o',
                'gpt-4o-mini' => 'gpt-4o-mini',
                'gpt-4-turbo' => 'gpt-4-turbo-preview',
                'gpt-3.5-turbo' => 'gpt-3.5-turbo',
            ],
            'default_model' => 'gpt-4o',
        ],

        'anthropic' => [
            'name' => 'Anthropic',
            'provider' => \Prism\Prism\Enums\Provider::Anthropic,
            'models' => [
                'claude-3-5-sonnet' => 'claude-3-5-sonnet-20241022',
                'claude-3-5-haiku' => 'claude-3-5-haiku-20241022',
                'claude-3-opus' => 'claude-3-opus-20240229',
                'claude-3-sonnet' => 'claude-3-sonnet-20240229',
            ],
            'default_model' => 'claude-3-5-sonnet',
        ],

        'gemini' => [
            'name' => 'Google Gemini',
            'provider' => \Prism\Prism\Enums\Provider::Gemini,
            'models' => [
                'gemini-1.5-pro' => 'gemini-1.5-pro',
                'gemini-1.5-flash' => 'gemini-1.5-flash',
                'gemini-pro' => 'gemini-pro',
            ],
            'default_model' => 'gemini-1.5-pro',
        ],

        'ollama' => [
            'name' => 'Ollama',
            'provider' => \Prism\Prism\Enums\Provider::Ollama,
            'models' => [
                'llama3.1' => 'llama3.1:8b',
                'llama3.1-instruct' => 'llama3.1:8b-instruct',
                'mistral' => 'mistral:7b',
                'codellama' => 'codellama:7b',
            ],
            'default_model' => 'llama3.1:8b-instruct',
        ],

        'mistral' => [
            'name' => 'Mistral AI',
            'provider' => \Prism\Prism\Enums\Provider::Mistral,
            'models' => [
                'mistral-large' => 'mistral-large-latest',
                'mistral-medium' => 'mistral-medium-latest',
                'mistral-small' => 'mistral-small-latest',
                'mistral-7b' => 'mistral-7b-instruct',
            ],
            'default_model' => 'mistral-large-latest',
        ],

        'groq' => [
            'name' => 'Groq',
            'provider' => \Prism\Prism\Enums\Provider::Groq,
            'models' => [
                'llama3.1-8b' => 'llama3.1-8b-8192',
                'llama3.1-70b' => 'llama3.1-70b-8192',
                'mixtral-8x7b' => 'mixtral-8x7b-32768',
                'gemma2-9b' => 'gemma2-9b-it',
            ],
            'default_model' => 'llama3.1-8b-8192',
        ],

        'xai' => [
            'name' => 'xAI',
            'provider' => \Prism\Prism\Enums\Provider::XAI,
            'models' => [
                'grok-beta' => 'grok-beta',
                'grok-2' => 'grok-2',
            ],
            'default_model' => 'grok-beta',
        ],

        'deepseek' => [
            'name' => 'DeepSeek',
            'provider' => \Prism\Prism\Enums\Provider::DeepSeek,
            'models' => [
                'deepseek-chat' => 'deepseek-chat',
                'deepseek-coder' => 'deepseek-coder',
                'deepseek-llm' => 'deepseek-llm-7b-chat',
            ],
            'default_model' => 'deepseek-chat',
        ],

        'voyageai' => [
            'name' => 'Voyage AI',
            'provider' => \Prism\Prism\Enums\Provider::VoyageAI,
            'models' => [
                'voyage-large-2' => 'voyage-large-2',
                'voyage-code-2' => 'voyage-code-2',
                'voyage-multilingual-2' => 'voyage-multilingual-2',
            ],
            'default_model' => 'voyage-large-2',
        ],

        'openrouter' => [
            'name' => 'OpenRouter',
            'provider' => \Prism\Prism\Enums\Provider::OpenRouter,
            'models' => [
                'openai/gpt-4o' => 'openai/gpt-4o',
                'openai/gpt-4o-mini' => 'openai/gpt-4o-mini',
                'anthropic/claude-3-5-sonnet' => 'anthropic/claude-3-5-sonnet',
                'anthropic/claude-3-5-haiku' => 'anthropic/claude-3-5-haiku',
                'google/gemini-1.5-pro' => 'google/gemini-1.5-pro',
                'meta-llama/llama-3.1-8b-instruct' => 'meta-llama/llama-3.1-8b-instruct',
            ],
            'default_model' => 'openai/gpt-4o',
            'supports_function_calling' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Configuration
    |--------------------------------------------------------------------------
    |
    | General settings for the financial advisor service.
    |
    */

    'max_steps' => env('FINANCIAL_ADVISOR_MAX_STEPS', 10),

    'temperature' => env('FINANCIAL_ADVISOR_TEMPERATURE', 0.7),

    'max_tokens' => env('FINANCIAL_ADVISOR_MAX_TOKENS', 2000),

    'enable_function_calling' => env('FINANCIAL_ADVISOR_ENABLE_FUNCTION_CALLING', true),

    /*
    |--------------------------------------------------------------------------
    | Memory Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for user memory management.
    |
    */

    'memory' => [
        'max_memories' => env('FINANCIAL_ADVISOR_MAX_MEMORIES', 100),
        'memory_retention_days' => env('FINANCIAL_ADVISOR_MEMORY_RETENTION_DAYS', 365),
        'importance_threshold' => env('FINANCIAL_ADVISOR_IMPORTANCE_THRESHOLD', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Categorization Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for purchase categorization.
    |
    */

    'categorization' => [
        'confidence_threshold' => env('FINANCIAL_ADVISOR_CATEGORIZATION_CONFIDENCE', 0.7),
        'fallback_category' => env('FINANCIAL_ADVISOR_FALLBACK_CATEGORY', 'Uncategorized'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for response formatting and structure.
    |
    */

    'response' => [
        'include_summary' => env('FINANCIAL_ADVISOR_INCLUDE_SUMMARY', true),
        'include_insights' => env('FINANCIAL_ADVISOR_INCLUDE_INSIGHTS', true),
        'include_recommendations' => env('FINANCIAL_ADVISOR_INCLUDE_RECOMMENDATIONS', true),
        'max_recommendations' => env('FINANCIAL_ADVISOR_MAX_RECOMMENDATIONS', 5),
    ],
];
