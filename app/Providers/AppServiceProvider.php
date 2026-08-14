<?php

namespace App\Providers;

use App\Models\Shop\MaterialBasePrice;
use App\Models\Shop\Product;
use App\Models\Shop\SystemTemplateItem;
use App\Observers\MaterialBasePriceObserver;
use App\Observers\ProductObserver;
use App\Observers\SystemTemplateItemObserver;
use App\Services\Scraping\CompetitorLinkScraperService;
use App\Services\Scraping\Scrapers\HoszigetelesPlazaScraper;
use App\Services\Scraping\Scrapers\HoszigetelorendszerScraper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CompetitorLinkScraperService::class, function (): CompetitorLinkScraperService {
            return new CompetitorLinkScraperService([
                new HoszigetelorendszerScraper,
                new HoszigetelesPlazaScraper,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Model::unguard();

        MaterialBasePrice::observe(MaterialBasePriceObserver::class);
        SystemTemplateItem::observe(SystemTemplateItemObserver::class);
        Product::observe(ProductObserver::class);

        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
