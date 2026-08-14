<?php

namespace App\Console\Commands;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportProducts extends Command
{
    protected $signature = 'shop:import-products
                            {--file=products.csv : CSV fájl neve a storage/app/imports/ mappában}
                            {--category= : Kategória ID, amelyhez a termékek rendelésre kerülnek}';

    protected $description = 'Termékek importálása CSV fájlból';

    const CSV_SEPARATOR = ';';

    const COLUMN_MAP = [
        'név' => 'name',
        'name' => 'name',
        'ár' => 'price',
        'price' => 'price',
        'slug' => 'slug',
        'sku' => 'sku',
        'cikkszám' => 'sku',
    ];

    const DEFAULT_IS_VISIBLE = false;

    const DEFAULT_PUBLISHED_AT = null;

    const DEFAULT_QTY = 99999;

    const DEFAULT_UNIT_SLUG = 'db';

    const DEFAULT_PRICING_MODE = 'manual';

    public function handle(): int
    {
        $filename = $this->option('file');
        $path = storage_path('app/imports/' . $filename);

        if (! file_exists($path)) {
            $this->error("CSV fájl nem található: {$path}");
            $this->line('Helyezd el a fájlt: storage/app/imports/' . $filename);

            return self::FAILURE;
        }

        $category = $this->resolveCategory();
        $unit = $this->resolveUnit();

        $content = file_get_contents($path);
        if (str_starts_with($content, "\xEF\xBB\xBF")) {
            $content = substr($content, 3);
        }

        $lines = array_values(array_filter(
            explode("\n", str_replace("\r\n", "\n", $content)),
            fn (string $l) => trim($l) !== '',
        ));
        $headers = array_map(
            fn (string $h) => self::COLUMN_MAP[mb_strtolower(trim($h))] ?? mb_strtolower(trim($h)),
            str_getcsv(array_shift($lines), self::CSV_SEPARATOR),
        );

        $this->line('Fejléc oszlopok: ' . implode(', ', $headers));

        $created = 0;
        $skipped = 0;

        $this->getOutput()->progressStart(count($lines));

        foreach ($lines as $lineNumber => $line) {
            $row = array_map('trim', str_getcsv($line, self::CSV_SEPARATOR));
            $data = array_combine($headers, array_pad($row, count($headers), ''));

            $name = $data['name'] ?? '';
            $sku = $data['sku'] ?? $data['slug'] ?? '';

            if (! $sku) {
                $sku = $this->generateSku($name);
            }

            if (! $name) {
                $skipped++;
                $this->getOutput()->progressAdvance();
                $this->components->twoColumnDetail(
                    "  <fg=yellow>KI #{$lineNumber}</> " . implode(' | ', array_filter($row)),
                    '<fg=yellow>üres name</>',
                );

                continue;
            }

            if ($sku && Product::where('sku', $sku)->exists()) {
                $skipped++;
                $this->getOutput()->progressAdvance();
                $this->components->twoColumnDetail(
                    "  <fg=yellow>KI #{$lineNumber}</> {$name}",
                    "<fg=yellow>SKU már létezik: {$sku}</>",
                );

                continue;
            }

            $product = Product::create([
                'name' => $name,
                'slug' => $this->uniqueSlug($name),
                'sku' => $sku ?: null,
                'price' => $this->parsePrice($data['price'] ?? ''),
                'qty' => self::DEFAULT_QTY,
                'is_visible' => self::DEFAULT_IS_VISIBLE,
                'published_at' => self::DEFAULT_PUBLISHED_AT ?? now(),
                'pricing_mode' => self::DEFAULT_PRICING_MODE,
                'requires_shipping' => false,
            ]);

            if ($unit) {
                $product->units()->attach($unit->id, ['is_primary' => true]);
            }

            if ($category) {
                $product->categories()->attach($category->id);
            }

            $created++;
            $this->getOutput()->progressAdvance();
        }

        $this->getOutput()->progressFinish();
        $this->info("Import kész! Létrehozva: {$created}, Kihagyva: {$skipped}");

        return self::SUCCESS;
    }

    private function resolveCategory(): ?Category
    {
        $categoryId = $this->option('category');

        if (! $categoryId) {
            return null;
        }

        $category = Category::find($categoryId);

        if (! $category) {
            $this->warn("A(z) {$categoryId} ID-jű kategória nem található — kategória nélkül importálunk.");
        }

        return $category;
    }

    private function resolveUnit(): ?Unit
    {
        if (! self::DEFAULT_UNIT_SLUG) {
            return null;
        }

        $unit = Unit::where('slug', self::DEFAULT_UNIT_SLUG)->first();

        if (! $unit) {
            $this->warn("A(z) '" . self::DEFAULT_UNIT_SLUG . "' egység nem található — egység nélkül importálunk.");
        }

        return $unit;
    }

    private function generateSku(string $name): string
    {
        $base = strtoupper(Str::slug($name));
        $sku = $base;
        $i = 1;

        while (Product::where('sku', $sku)->exists()) {
            $sku = $base . '-' . $i++;
        }

        return $sku;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 1;

        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function parsePrice(string $raw): ?float
    {
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace([' ', "\u{00A0}"], '', $raw);
        $normalized = str_replace(',', '.', $normalized);

        return is_numeric($normalized) ? (float) $normalized : null;
    }
}
