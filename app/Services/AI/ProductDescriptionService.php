<?php

namespace App\Services\AI;

use App\Models\AiGenerationLog;
use App\Models\Shop\Product;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Enums\Provider;
use Prism\Prism\Facades\Prism;
use Throwable;

class ProductDescriptionService
{
    private const ALLOWED_HTML_TAGS = ['h2', 'h3', 'p', 'ul', 'ol', 'li', 'strong', 'em', 'br'];

    /**
     * Generate description and seo_description for a product using AI.
     *
     * @return array{description: string, seo_description: string, ai_description_generated_at: Carbon}
     *
     * @throws Throwable
     */
    public function generate(Product $product, string $provider, string $systemPrompt): array
    {
        $model = config("ai.models.{$provider}");
        $startedAt = now();
        $startMs = (int) (microtime(true) * 1000);

        try {
            $response = Prism::text()
                ->using($this->resolveProvider($provider), $model)
                ->withSystemPrompt($systemPrompt)
                ->withPrompt($this->buildProductPrompt($product))
                ->generate();

            $durationMs = (int) (microtime(true) * 1000) - $startMs;
            $result = $this->parseResponse($response->text);

            AiGenerationLog::create([
                'product_id' => $product->id,
                'provider' => $provider,
                'model' => $model,
                'status' => 'success',
                'error_message' => null,
                'duration_ms' => $durationMs,
                'generated_at' => $startedAt,
            ]);

            Log::info('AI description generated', [
                'product_id' => $product->id,
                'provider' => $provider,
                'model' => $model,
                'duration_ms' => $durationMs,
            ]);

            return array_merge($result, ['ai_description_generated_at' => $startedAt]);
        } catch (Throwable $e) {
            $durationMs = (int) (microtime(true) * 1000) - $startMs;

            AiGenerationLog::create([
                'product_id' => $product->id,
                'provider' => $provider,
                'model' => $model,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'duration_ms' => $durationMs,
                'generated_at' => $startedAt,
            ]);

            Log::error('AI description generation failed', [
                'product_id' => $product->id,
                'provider' => $provider,
                'model' => $model,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function resolveProvider(string $provider): Provider
    {
        return match ($provider) {
            'openai' => Provider::OpenAI,
            default => Provider::Anthropic,
        };
    }

    private function buildProductPrompt(Product $product): string
    {
        $product->loadMissing(['brand', 'categories', 'attributeValues.attribute', 'attributeValues.options']);

        $lines = ["Termék neve: {$product->name}"];

        if ($product->brand) {
            $lines[] = "Gyártó/márka: {$product->brand->name}";
        }

        $categories = $product->categories->pluck('name')->filter()->implode(', ');
        if ($categories) {
            $lines[] = "Kategóriák: {$categories}";
        }

        if ($product->sku) {
            $lines[] = "Cikkszám (SKU): {$product->sku}";
        }

        foreach ($product->attributeValues as $attrValue) {
            if (! $attrValue->attribute) {
                continue;
            }

            $name = $attrValue->attribute->name;
            $value = $this->resolveAttributeValueLabel($attrValue);

            if ($value !== null && $value !== '') {
                $lines[] = "{$name}: {$value}";
            }
        }

        $lines[] = '';
        $lines[] = 'Generálj ehhez a termékhez:';
        $lines[] = '1. description: HTML formátumú termékleírás (h2, h3, p, ul, ol, li, strong, em, br tagokat használhatsz)';
        $lines[] = '2. seo_description: max 160 karakter plain text meta leírás';
        $lines[] = '';
        $lines[] = 'Válaszolj kizárólag valid JSON formátumban, így:';
        $lines[] = '{"description": "<p>...</p>", "seo_description": "..."}';

        return implode("\n", $lines);
    }

    private function resolveAttributeValueLabel(mixed $attrValue): ?string
    {
        if ($attrValue->number_value !== null) {
            $unit = $attrValue->attribute->unit ?? '';

            return $attrValue->number_value . ($unit ? " {$unit}" : '');
        }

        if ($attrValue->text_value !== null) {
            return $attrValue->text_value;
        }

        if ($attrValue->relationLoaded('options') && $attrValue->options->isNotEmpty()) {
            return $attrValue->options->pluck('label')->implode(', ');
        }

        return null;
    }

    /**
     * Parse AI response text to extract description and seo_description.
     *
     * @return array{description: string, seo_description: string}
     */
    public function parseResponse(string $text): array
    {
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $cleaned = preg_replace('/```\s*$/m', '', $cleaned ?? $text);
        $cleaned = trim($cleaned ?? $text);

        $data = json_decode($cleaned, true);

        if (! is_array($data)) {
            if (preg_match('/\{[\s\S]*\}/u', $cleaned, $matches)) {
                $data = json_decode($matches[0], true);
            }
        }

        $description = '';
        $seoDescription = '';

        if (is_array($data)) {
            $description = (string) ($data['description'] ?? '');
            $seoDescription = (string) ($data['seo_description'] ?? '');
        }

        $allowedTagString = '<' . implode('><', self::ALLOWED_HTML_TAGS) . '>';
        $description = strip_tags($description, $allowedTagString);

        $seoDescription = strip_tags($seoDescription);
        if (mb_strlen($seoDescription) > 160) {
            $seoDescription = mb_substr($seoDescription, 0, 157) . '...';
        }

        return [
            'description' => $description,
            'seo_description' => $seoDescription,
        ];
    }
}
