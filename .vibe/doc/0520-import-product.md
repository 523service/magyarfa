# Termék import CSV-ből

## Artisan parancs

```bash
php artisan shop:import-products [--file=] [--category=]
```

## Opciók

| Opció | Leírás | Alapértelmezett |
|-------|--------|-----------------|
| `--file=` | CSV fájl neve a `storage/app/imports/` mappában | `products.csv` |
| `--category=` | Kategória ID — minden importált termék ebbe kerül | *(nincs)* |

## CSV formátum

- **Elválasztó:** pontosvessző (`;`)
- **Kódolás:** UTF-8
- **Első sor:** fejléc (`name;sku;price`)

```
name;sku;price
Revco Vario Fix polisztirol ragasztó;polisztirol-ragaszto;2922
Thermodam Polisztirol ragasztó;polisztirol-ragaszto-thermodam;2750
Ragasztóhab EPS-XPS pisztolyos 750ml;;3250
```

### Oszlopok

| Oszlop | Kötelező | Leírás |
|--------|----------|--------|
| `name` | igen | Termék neve |
| `sku` | nem | Cikkszám — ha megadva, duplikáció ellenőrzésre használja |
| `price` | nem | Ár (egész szám vagy tizedes) |

> A `slug` automatikusan generálódik a névből — nem kell megadni a CSV-ben.

## Fájl elhelyezése

```
storage/
  app/
    imports/
      products.csv        ← alapértelmezett
      ragaszto.csv        ← egyedi --file= esetén
```

## Példák

### Alapértelmezett import (products.csv, kategória nélkül)

```bash
php artisan shop:import-products
```

### Egyedi fájlnév

```bash
php artisan shop:import-products --file=ragaszto.csv
```

### Kategóriához rendelve

```bash
php artisan shop:import-products --category=32
```

### Egyedi fájl + kategória

```bash
php artisan shop:import-products --file=ragaszto.csv --category=32
```

### Seederből (kategória és fájl nélkül)

```bash
php artisan db:seed --class=ProductImportSeeder
```

## Alapértelmezett értékek

A parancs ezekkel az értékekkel hozza létre a termékeket:

| Mező | Érték |
|------|-------|
| `is_visible` | `false` — nem jelenik meg a shopban |
| `qty` | `99999` |
| `pricing_mode` | `manual` |
| `published_at` | mai dátum |
| `requires_shipping` | `false` |
| egység | `db` slug alapján (ha nem található, figyelmeztetés) |

## Fontos megjegyzések

- Ha `sku` meg van adva és már létezik az adatbázisban, a sor kihagyásra kerül (duplikáció védelem).
- Ha `--category` ID nem található, a parancs figyelmeztet, de folytatja egység nélkül.
- A szeparátor (`';'`) a `ImportProducts::CSV_SEPARATOR` konstansban állítható.
