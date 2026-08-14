<?php

namespace App\Console\Commands;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'app:generate-sitemap';

    protected $description = 'Generate the public/sitemap.xml file';

    public function handle(): int
    {
        $sitemap = Sitemap::create();

        $sitemap->add(
            Url::create(route('home'))
                ->setPriority(1.0)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
        );

        Category::query()
            ->where('is_visible', true)
            ->orderBy('id')
            ->each(function (Category $category) use ($sitemap): void {
                $sitemap->add(
                    tap(
                        Url::create(route('category.show', $category->slug))
                            ->setPriority(0.8)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY),
                        fn (Url $url) => $category->updated_at && $url->setLastModificationDate($category->updated_at)
                    )
                );
            });

        Product::query()
            ->where('is_visible', true)
            ->orderBy('id')
            ->each(function (Product $product) use ($sitemap): void {
                $sitemap->add(
                    tap(
                        Url::create(route('product.show', $product->slug))
                            ->setPriority(0.9)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY),
                        fn (Url $url) => $product->updated_at && $url->setLastModificationDate($product->updated_at)
                    )
                );
            });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated: ' . public_path('sitemap.xml'));

        return self::SUCCESS;
    }
}
