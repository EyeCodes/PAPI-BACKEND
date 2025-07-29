<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class FormulaParser
{
    public function evaluate(string $formula, array $variables): int
    {
        // Very basic and unsafe evaluator; should be replaced with a real parser or sandboxed interpreter
        extract($variables);
        try {
            // WARNING: eval is dangerous. Only for trusted input.
            $result = eval("return (int) ($formula);");
            return is_numeric($result) ? (int) $result : 0;
        } catch (\Throwable $e) {
            Log::error('Formula evaluation failed', ['error' => $e->getMessage()]);
            return 0;
        }
    }
}
