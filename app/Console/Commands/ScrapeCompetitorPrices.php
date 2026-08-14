<?php

namespace App\Console\Commands;

use App\Jobs\ScrapeCompetitorLinkJob;
use App\Models\Shop\CompetitorLink;
use App\Models\Shop\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ScrapeCompetitorPrices extends Command
{
    protected $signature = 'scrape:competitor-prices
                            {--product= : Termék ID vagy slug}
                            {--delay=5 : Másodperc két job között (rate limiting)}';

    protected $description = 'Konkurencia árak scrape-elése a megadott termékekhez';

    public function handle(): int
    {
        $delay = (int) $this->option('delay');
        $productOption = $this->option('product');

        $links = $this->resolveLinks($productOption);

        if ($links->isEmpty()) {
            $this->warn('Nincs scrape-elendő konkurencia link.');

            return self::SUCCESS;
        }

        $this->line("Feldolgozandó linkek: <fg=cyan>{$links->count()}</>");
        $this->newLine();

        $this->getOutput()->progressStart($links->count());

        $links->each(function (CompetitorLink $link, int $index) use ($delay): void {
            $delaySeconds = $index * $delay;

            ScrapeCompetitorLinkJob::dispatch($link)
                ->delay(now()->addSeconds($delaySeconds));

            $this->getOutput()->progressAdvance();
            $this->components->twoColumnDetail(
                "  <fg=green>#{$link->id}</> {$link->competitor_name}",
                "delay: {$delaySeconds}s",
            );
        });

        $this->getOutput()->progressFinish();
        $this->newLine();
        $this->info('Scraping jobbok sikeresen sorba állítva.');

        return self::SUCCESS;
    }

    private function resolveLinks(?string $productOption): Collection
    {
        if ($productOption === null) {
            return CompetitorLink::all();
        }

        $product = is_numeric($productOption)
            ? Product::find((int) $productOption)
            : Product::where('slug', $productOption)->first();

        if ($product === null) {
            $this->error("Termék nem található: {$productOption}");

            return collect();
        }

        $this->components->twoColumnDetail('Termék', $product->name);

        return $product->competitorLinks()->get();
    }
}
