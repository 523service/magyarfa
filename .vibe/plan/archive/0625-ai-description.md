# Terv: AI-alapú termékleírás generálás (Prism)

## Kontextus

A termékekhez (`description`, `seo_description`) jelenleg manuálisan kell leírást írni. A cél: Filament adminban egyetlen kattintással lehessen AI-jal generálni ezeket a mezőket — választható provider (Anthropic / OpenAI), szerkeszthető prompt, megerősítés meglévő leírás felülírása előtt. Bulk akcióval több termékhez egyszerre is futtatható (szinkron vagy queue, config-ból állítható).

---

## 1. Csomag telepítés

```bash
composer require prism-php/prism
php artisan vendor:publish --tag=prism-config
```

A `../../../config/prism.php`-ben és `../../../.env`-ben kerülnek az API kulcsok:
- `ANTHROPIC_API_KEY`
- `OPENAI_API_KEY`

---

## 2. Config — `../../../config/ai.php` (új fájl)

```php
return [
    'default_provider' => env('AI_DEFAULT_PROVIDER', 'anthropic'),
    'bulk_mode'        => env('AI_BULK_MODE', 'sync'), // 'sync' | 'queue'
    'models' => [
        'anthropic' => env('AI_ANTHROPIC_MODEL', 'claude-haiku-4-5-20251001'),
        'openai'    => env('AI_OPENAI_MODEL', 'gpt-4o-mini'),
    ],
    'system_prompt' => env('AI_SYSTEM_PROMPT', 'Te egy profi marketingszöveg-író vagy, aki szigetelési és építőipari termékek leírásait írja. A leírás legyen magyar nyelvű, szakmai de olvasható, SEO-barát, HTML formátumú (h2, p, ul tagokat használhatsz). A seo_description legyen tömör, max 160 karakter, plain text.'),
];
```

---

## 3. Service — `../../../app/Services/AI/ProductDescriptionService.php` (új)

Felelőssége:
- Product adataiból prompt összeállítás (name, brand, categories, attributes)
- Prism hívás a megadott providerrel és modellel
- JSON-ból kinyeri: `description` (HTML) + `seo_description` (plain, max 160 char)
- Visszatér: `['description' => '...', 'seo_description' => '...']`

```php
use EchoLabs\Prism\Prism;
use EchoLabs\Prism\Enums\Provider;

class ProductDescriptionService
{
    public function generate(Product $product, string $provider, string $systemPrompt): array
    {
        $productPrompt = $this->buildProductPrompt($product);
        $prismProvider = $provider === 'anthropic' ? Provider::Anthropic : Provider::OpenAI;
        $model = config("ai.models.{$provider}");

        $response = Prism::text()
            ->using($prismProvider, $model)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($productPrompt)
            ->generate();

        return $this->parseResponse($response->text);
    }

    private function buildProductPrompt(Product $product): string { ... }
    private function parseResponse(string $text): array { ... }
}
```

A prompt tartalmazza: termék neve, márka (ha van), kategóriák, attribútumok kulcs-érték párjai.

Az AI-tól JSON-t kérünk vissza:
```json
{"description": "<p>...</p>", "seo_description": "...max 160 char..."}
```

---

## 4. Job — `../../../app/Jobs/GenerateProductDescriptionJob.php` (új)

```php
class GenerateProductDescriptionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

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
```

---

## 5. Filament Actions — `ProductsTable.php` módosítás

### 5a. Sor (row) akció — szinkron

```php
Action::make('generateDescription')
    ->label('AI Leírás')
    ->icon('heroicon-o-sparkles')
    ->color('info')
    ->form([
        Select::make('provider')
            ->label('AI Provider')
            ->options(['anthropic' => 'Anthropic (Claude)', 'openai' => 'OpenAI (GPT)'])
            ->default(config('ai.default_provider'))
            ->required(),
        Textarea::make('system_prompt')
            ->label('Rendszer prompt')
            ->rows(5)
            ->default(config('ai.system_prompt')),
    ])
    ->action(function ($record, array $data, ProductDescriptionService $service): void {
        $result = $service->generate($record, $data['provider'], $data['system_prompt']);
        $record->update($result);
        Notification::make()->success()->title('AI leírás generálva')->send();
    })
    ->requiresConfirmation(fn ($record) => filled($record->description))
    ->modalHeading('Meglévő leírás felülírása?')
    ->modalDescription('Ennek a terméknek már van leírása. Biztosan felülírja az AI generált szöveggel?')
```

### 5b. Bulk akció — sync/queue config-vezérelt

```php
BulkAction::make('generateDescriptions')
    ->label('AI Leírás generálás')
    ->icon('heroicon-o-sparkles')
    ->form([
        Select::make('provider') ...,
        Textarea::make('system_prompt') ...,
        Toggle::make('overwrite')
            ->label('Meglévő leírások felülírása')
            ->default(false),
    ])
    ->action(function (Collection $records, array $data, ProductDescriptionService $service): void {
        $useQueue = config('ai.bulk_mode') === 'queue';
        $count = 0;
        foreach ($records as $product) {
            if (filled($product->description) && ! $data['overwrite']) continue;
            if ($useQueue) {
                GenerateProductDescriptionJob::dispatch($product, $data['provider'], $data['system_prompt']);
            } else {
                $result = $service->generate($product, $data['provider'], $data['system_prompt']);
                $product->update($result);
            }
            $count++;
        }
        Notification::make()->success()
            ->title("{$count} termék leírása " . ($useQueue ? 'sorba rakva' : 'generálva'))
            ->send();
    })
```

---

## 6. Érintett fájlok

| Fájl | Változás |
|------|----------|
| `../../../composer.json` | `prism-php/prism` csomag hozzáadás |
| `../../../config/prism.php` | Vendor publish után létrejön |
| `../../../config/ai.php` | **Új** — provider defaults, bulk_mode, system_prompt, models |
| `../../../.env` / `../../../.env.example` | `ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, `AI_DEFAULT_PROVIDER`, `AI_BULK_MODE` |
| `../../../app/Services/AI/ProductDescriptionService.php` | **Új** |
| `../../../app/Jobs/GenerateProductDescriptionJob.php` | **Új** |
| `../../../app/Filament/Clusters/Products/Resources/Products/Tables/ProductsTable.php` | Row + bulk akció hozzáadás |

---

## 7. Tesztelés

1. `../../../tests/Feature/AI/ProductDescriptionServiceTest.php` — Prism fake-et használva:
   - Happy path: generate() visszatér description + seo_description párral
   - Üres product adatok esetén is generál
   - Provider selection helyes modellt hív

2. `tests/Feature/AI/GenerateProductDescriptionJobTest.php`:
   - Job lefut, product frissül

3. Kézi teszt Filamentben:
   - Egyetlen termék → gomb → modal → generálás → mezők frissülnek az edit formon
   - Bulk: 3 termék kijölése → AI Leírás → provider választás → generálás
   - Meglévő leírással: megerősítő modal megjelenik

---

## Végrehajtás sorrendje

1. `composer require prism-php/prism` + vendor:publish
2. `../../../config/ai.php` létrehozása
3. `../../../.env.example` frissítés
4. `ProductDescriptionService` megírás
5. Tesztek írása (service szint)
6. `GenerateProductDescriptionJob` megírás
7. `ProductsTable.php` akciók hozzáadása
8. Pint futtatás
9. Tesztek futtatása
