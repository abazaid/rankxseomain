<?php

declare(strict_types=1);

namespace App\Services;

use App\Config;

final class OpenAICostCalculator
{
    public function calculate(array $usage): array
    {
        $inputTokens = (int) ($usage['input_tokens'] ?? 0);
        $outputTokens = (int) ($usage['output_tokens'] ?? 0);
        $cachedInputTokens = (int) ($usage['input_tokens_details']['cached_tokens'] ?? 0);
        $billableInputTokens = max($inputTokens - $cachedInputTokens, 0);

        $inputRate = (float) Config::get('OPENAI_PRICE_INPUT_PER_MILLION', 0.25);
        $cachedInputRate = (float) Config::get('OPENAI_PRICE_CACHED_INPUT_PER_MILLION', 0.025);
        $outputRate = (float) Config::get('OPENAI_PRICE_OUTPUT_PER_MILLION', 2.00);

        $inputCost = ($billableInputTokens / 1000000) * $inputRate;
        $cachedInputCost = ($cachedInputTokens / 1000000) * $cachedInputRate;
        $outputCost = ($outputTokens / 1000000) * $outputRate;
        $totalCost = $inputCost + $cachedInputCost + $outputCost;

        return [
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cached_input_tokens' => $cachedInputTokens,
            'total_tokens' => (int) ($usage['total_tokens'] ?? ($inputTokens + $outputTokens)),
            'input_cost_usd' => round($inputCost + $cachedInputCost, 6),
            'output_cost_usd' => round($outputCost, 6),
            'total_cost_usd' => round($totalCost, 6),
        ];
    }
}
