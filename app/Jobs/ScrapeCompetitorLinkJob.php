<?php

namespace App\Jobs;

use App\Models\Shop\CompetitorLink;
use App\Services\Scraping\CompetitorLinkScraperService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ScrapeCompetitorLinkJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 60;

    public int $retryAfter = 60;

    public function __construct(public readonly CompetitorLink $link) {}

    public function handle(CompetitorLinkScraperService $service): void
    {
        $service->scrape($this->link);
    }
}
