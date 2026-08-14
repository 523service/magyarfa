<?php

namespace App\Jobs;

use App\Models\Shop\Product;
use App\Services\AI\ProductDescriptionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateProductDescriptionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $retryAfter = 90;

    public function __construct(
        public readonly Product $product,
        public readonly string $provider,
        public readonly string $systemPrompt,
    ) {}

    public function handle(ProductDescriptionService $service): void
    {
        $result = $service->generate($this->product, $this->provider, $this->systemPrompt);
        $this->product->update($result);
    }
}
