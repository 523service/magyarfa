# Mennyiségi egységek (Units) implementációs terv

## Összefoglaló

Mennyiségi egységek kezelésének bevezetése termékekhez és kategóriákhoz pivot táblákkal (many-to-many kapcsolat).

## Adatbázis struktúra

### 1. `shop_units` tábla
```
id, name, slug, timestamps
```

### 2. `shop_product_unit` pivot tábla
```
id, shop_product_id, shop_unit_id, is_primary (boolean), timestamps
```

### 3. `shop_category_unit` pivot tábla
```
id, shop_category_id, shop_unit_id, is_primary (boolean), timestamps
```

## Fallback logika (storefront)

1. Ha a terméknek van `is_primary=true` egysége → azt használjuk
2. Ha nincs → a termék első kategóriájának `is_primary=true` egysége
3. Ha az sincs → "db" (alapértelmezett)

## Seed adatok (17 egység)

| name | slug |
|------|------|
| db | db |
| m² | m2 |
| m³ | m3 |
| Raklap | raklap |
| Bála | bala |
| Folyóméter | folyometer |
| Zsák | zsak |
| Tekercs | tekercs |
| Szál | szal |
| Kaloda | kaloda |
| Vödör | vodor |
| Pár | par |
| Csomag | csomag |
| kg | kg |
| Karton | karton |
| Tábla | tabla |
| Liter | liter |

---

## Létrehozandó fájlok

### Migrációk
1. `database/migrations/xxxx_create_shop_units_table.php`
2. `database/migrations/xxxx_create_shop_product_unit_table.php`
3. `database/migrations/xxxx_create_shop_category_unit_table.php`

### Model
4. `app/Models/Shop/Unit.php`

### Factory & Seeder
5. `database/factories/Shop/UnitFactory.php`
6. `database/seeders/UnitSeeder.php`

### Filament Resource (Products cluster alatt)
7. `app/Filament/Clusters/Products/Resources/Units/UnitResource.php`
8. `app/Filament/Clusters/Products/Resources/Units/Schemas/UnitForm.php`
9. `app/Filament/Clusters/Products/Resources/Units/Tables/UnitsTable.php`
10. `app/Filament/Clusters/Products/Resources/Units/Pages/ListUnits.php`
11. `app/Filament/Clusters/Products/Resources/Units/Pages/CreateUnit.php`
12. `app/Filament/Clusters/Products/Resources/Units/Pages/EditUnit.php`

### Tesztek
13. `tests/Feature/UnitTest.php`

---

## Módosítandó fájlok

### Models
1. **`app/Models/Shop/Product.php`**
   - `units()` BelongsToMany kapcsolat (withPivot: is_primary)
   - `getDisplayUnitAttribute()` accessor a fallback logikával

2. **`app/Models/Shop/Category.php`**
   - `units()` BelongsToMany kapcsolat (withPivot: is_primary)

### Filament Forms
3. **`app/Filament/Clusters/Products/Resources/Products/Schemas/ProductForm.php`**
   - Unit multi-select az Associations szekcióban
   - Toggle az elsődleges egység kiválasztásához

4. **`app/Filament/Clusters/Products/Resources/Categories/Schemas/CategoryForm.php`**
   - Unit multi-select és primary toggle

### Filament Tables
5. **`app/Filament/Clusters/Products/Resources/Products/Tables/ProductsTable.php`**
   - `display_unit` oszlop hozzáadása

6. **`app/Filament/Clusters/Products/Resources/Categories/Tables/CategoriesTable.php`**
   - Unit oszlop hozzáadása

### Seeder
7. **`database/seeders/DatabaseSeeder.php`**
   - UnitSeeder hívása a többi seeder előtt

---

## Implementációs sorrend

1. Migrációk létrehozása és futtatása
2. Unit model létrehozása
3. Product model frissítése (units kapcsolat + accessor)
4. Category model frissítése (units kapcsolat)
5. UnitFactory létrehozása
6. UnitSeeder létrehozása a 17 egységgel
7. UnitResource + Form + Table + Pages létrehozása
8. ProductForm frissítése unit kiválasztással
9. CategoryForm frissítése unit kiválasztással
10. ProductsTable frissítése unit oszloppal
11. CategoriesTable frissítése unit oszloppal
12. DatabaseSeeder frissítése
13. Pint futtatása formázáshoz
14. Tesztek írása és futtatása

---

## Verifikáció

```bash
# Migrációk futtatása
php artisan migrate

# Egységek seedelése
php artisan db:seed --class=UnitSeeder

# Teljes friss adatbázis
php artisan migrate:fresh --seed

# Tesztek futtatása
php artisan test --filter=Unit

# Kód formázás
vendor/bin/pint --dirty
```

## Manuális tesztelés

1. Admin panel → Products cluster → Units: CRUD műveletek
2. Product szerkesztés → Egység(ek) kiválasztása, primary beállítása
3. Category szerkesztés → Alapértelmezett egység(ek) beállítása
4. Product lista → Megjelenik a display_unit oszlop
5. Fallback logika ellenőrzése:
   - Termék saját egységgel → azt mutatja
   - Termék egység nélkül, de kategóriával → kategória egységét mutatja
   - Egyik sincs → "db"-t mutat