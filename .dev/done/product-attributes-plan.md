# Product Attributes (EAV) Implementation Plan

## Overview

Rugalmas EAV (Entity-Attribute-Value) rendszer implementalasa termek attributumokhoz. Az adminisztratorok kod modositas nelkul hozhatnak letre uj attributumokat.

## Database Schema

### 1. `shop_attributes` tabla
Attributum definiciok tarolasa.

| Mezo                     | Tipus    | Leiras                                     |
|--------------------------|----------|--------------------------------------------|
| id                       | bigint   | Primary key                                |
| name                     | string   | Nev (pl. "Szin", "Magassag")               |
| slug                     | string   | Unique azonosito                           |
| type                     | string   | text, number, select, multiselect, boolean |
| unit                     | string?  | Mertekegyseg (pl. "cm", "kg")              |
| is_required              | boolean  | Kotelezo-e                                 |
| is_filterable            | boolean  | Szurheto-e frontend-en                     |
| is_visible               | boolean  | Lathato-e                                  |
| sort_order               | int      | Sorrend                                    |
| timestamps, soft_deletes |          |                                            |

### 2. `shop_attribute_options` tabla
Elore definialt ertekek select/multiselect tipusokhoz.

| Mezo              | Tipus  | Leiras                     |
|-------------------|--------|----------------------------|
| id                | bigint | Primary key                |
| shop_attribute_id | FK     | Attributum kapcsolat       |
| value             | string | Ertek (pl. "Piros", "Kek") |
| sort_order        | int    | Sorrend                    |
| timestamps        |        |                            |

### 3. `shop_product_attribute_values` tabla
Termek-attributum ertek kapcsolatok.

| Mezo              | Tipus    | Leiras               |
|-------------------|----------|----------------------|
| id                | bigint   | Primary key          |
| shop_product_id   | FK       | Termek kapcsolat     |
| shop_attribute_id | FK       | Attributum kapcsolat |
| text_value        | text?    | Szoveges ertek       |
| number_value      | decimal? | Szam ertek           |
| boolean_value     | boolean? | Igen/nem ertek       |
| timestamps        |          |                      |

### 4. `shop_product_attribute_value_options` pivot tabla
Multiselect ertekek tarolasa.

| Mezo                            | Tipus  | Leiras           |
|---------------------------------|--------|------------------|
| id                              | bigint | Primary key      |
| shop_product_attribute_value_id | FK     | Ertek kapcsolat  |
| shop_attribute_option_id        | FK     | Option kapcsolat |
| timestamps                      |        |                  |

## Eloquent Models

### Uj fajlok:
- `app/Models/Shop/Attribute.php` - Attributum definicio
- `app/Models/Shop/AttributeOption.php` - Elore definialt ertekek
- `app/Models/Shop/ProductAttributeValue.php` - Termek attributum ertekek

### Modositando:
- `app/Models/Shop/Product.php` - Uj `attributeValues()` relacio

## Filament Admin Struktura

### Uj AttributeResource
```
app/Filament/Clusters/Products/Resources/Attributes/
├── AttributeResource.php
├── Schemas/
│   └── AttributeForm.php
├── Tables/
│   └── AttributesTable.php
├── Pages/
│   ├── ListAttributes.php
│   ├── CreateAttribute.php
│   └── EditAttribute.php
└── RelationManagers/
    └── OptionsRelationManager.php
```

### ProductForm modositas
Uj "Attributes" Section hozzaadasa a jobb oldalon (Associations Section utan):
- Dinamikusan generalja a form mezoket az attributumok alapjan
- Attributum tipus alapjan: TextInput, Select, Toggle, stb.

### CreateProduct es EditProduct modositas
- `mutateFormDataBeforeFill()` - Betolti a meglevo attributum ertekeket
- `afterSave()` / `afterCreate()` - Elmenti az attributum ertekeket

## Seeder - Alapertelmezett Attributumok

| Nev        | Tipus       | Mertekegyseg | Options                                                 |
|------------|-------------|--------------|---------------------------------------------------------|
| Magassag   | number      | cm           | -                                                       |
| Szelesseg  | number      | cm           | -                                                       |
| Hosszusag  | number      | cm           | -                                                       |
| Melyseg    | number      | cm           | -                                                       |
| Suly       | number      | kg           | -                                                       |
| Szin       | select      | -            | Feher, Fekete, Piros, Kek, Zold, Sarga, Szurke, Barna   |
| Kiszereles | select      | -            | Darabos, Csomag (5db), Csomag (10db), Dobozos, Palettas |
| Anyag      | multiselect | -            | EPS, XPS, Grafit, Polisztirol, Uveggyapot, Kozetgyapot  |

## Uj Fajlok Listaja

### Migrations (4 db)
1. `database/migrations/xxxx_create_shop_attributes_table.php`
2. `database/migrations/xxxx_create_shop_attribute_options_table.php`
3. `database/migrations/xxxx_create_shop_product_attribute_values_table.php`
4. `database/migrations/xxxx_create_shop_product_attribute_value_options_table.php`

### Models (3 db)
1. `app/Models/Shop/Attribute.php`
2. `app/Models/Shop/AttributeOption.php`
3. `app/Models/Shop/ProductAttributeValue.php`

### Filament Resource (8 db)
1. `app/Filament/Clusters/Products/Resources/Attributes/AttributeResource.php`
2. `app/Filament/Clusters/Products/Resources/Attributes/Schemas/AttributeForm.php`
3. `app/Filament/Clusters/Products/Resources/Attributes/Tables/AttributesTable.php`
4. `app/Filament/Clusters/Products/Resources/Attributes/Pages/ListAttributes.php`
5. `app/Filament/Clusters/Products/Resources/Attributes/Pages/CreateAttribute.php`
6. `app/Filament/Clusters/Products/Resources/Attributes/Pages/EditAttribute.php`
7. `app/Filament/Clusters/Products/Resources/Attributes/RelationManagers/OptionsRelationManager.php`

### Factories (2 db)
1. `database/factories/Shop/AttributeFactory.php`
2. `database/factories/Shop/AttributeOptionFactory.php`

### Seeder (1 db)
1. `database/seeders/AttributeSeeder.php`

## Modositando Fajlok

1. `app/Models/Shop/Product.php` - Uj relacio
2. `app/Filament/Clusters/Products/Resources/Products/Schemas/ProductForm.php` - Attributes section
3. `app/Filament/Clusters/Products/Resources/Products/Pages/CreateProduct.php` - afterCreate
4. `app/Filament/Clusters/Products/Resources/Products/Pages/EditProduct.php` - mutateFormDataBeforeFill, afterSave
5. `database/seeders/DatabaseSeeder.php` - AttributeSeeder hivasa

## Implementacios Sorrend

1. Migraciok letrehozasa es futtatasa
2. Modellek letrehozasa (Attribute, AttributeOption, ProductAttributeValue)
3. Product model frissitese (attributeValues relacio)
4. Factory-k letrehozasa
5. AttributeSeeder letrehozasa
6. Filament AttributeResource es kapcsolodo fajlok
7. ProductForm modositasa (dinamikus attributum mezok)
8. CreateProduct es EditProduct modositasa (adat kezeles)
9. DatabaseSeeder frissitese
10. Migracio es seed futtatasa: `php artisan migrate && php artisan db:seed --class=AttributeSeeder`

## Verifikacio

1. **Attribute CRUD tesztelese:**
   - Admin feluleten uj attributum letrehozasa
   - Tipus valtas eseten a form dinamikusan valtozik
   - Select/multiselect tipusnal Options tab megjelenik
   - Options hozzaadasa, szerkesztese, torlese

2. **Product attributumok tesztelese:**
   - Termek szerkesztesekor megjelennek az attributum mezok
   - Attributum ertekek mentese es visszatoltese
   - Uj termek letrehozasakor is mukodik

3. **Tesztek futtatasa:**
   ```bash
   php artisan test --filter=Attribute
   php artisan test --filter=Product
   ```

4. **Pint formatas:**
   ```bash
   vendor/bin/pint --dirty
   ```
