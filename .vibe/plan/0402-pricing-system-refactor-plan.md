# Pricing System Refactor Plan for Claude Code

## Goal
Refactor the current bulk pricing / dynamic base price system into a simpler, explicit, domain-specific pricing model for insulation boards and insulation systems.

The current implementation is too generic, too hard to understand, and too easy to misconfigure. The new design must be easier to maintain, easier to explain to admins, and safer for daily price updates.

---

## Core Product Pricing Strategy

We do **not** want a universal low-code pricing engine.

We want a **domain-specific pricing system** for the insulation webshop.

Each product must use exactly **one** pricing mode:

1. `manual`
2. `formula`
3. `system_template`

No hidden multi-level fallback logic.
No implicit magic.
No guessing where the price comes from.

If an admin opens a product, they must immediately understand how the price is calculated.

---

## Main Problems With the Current System

The current system mixes too many responsibilities:

- manual price override
- simple dynamic pricing
- recipe-based system pricing
- product-level component overrides
- attribute-based quantity lookup
- generic fallback chains

This causes multiple issues:

- hard to understand
- hard to debug
- easy to misconfigure
- difficult to explain to non-technical admins
- hidden price source priority
- higher risk during daily material price updates

---

## Target Architecture

We split pricing into clear concepts.

### 1. Material Prices
These are the daily editable prices of raw materials / pricing inputs.

Examples:
- EPS Standard
- Graphite EPS
- Rockwool
- Adhesive
- Mesh
- Primer
- Plaster

### 2. System Templates
These define how a full insulation system is built from material prices.

Examples:
- EPS Standard System
- Graphite EPS System
- Rockwool System

### 3. Products
A product only declares which pricing mode it uses and which source it depends on.

---

## Pricing Modes

### A. Manual
Use for products with fixed prices.

Fields:
- `pricing_mode = manual`
- `manual_price`

Rules:
- frontend price comes directly from `manual_price`
- no material dependency
- no formula calculation
- no system template calculation

### B. Formula
Use for products where price comes from one material price and one simple rule.

Typical use case:
- insulation boards where price depends on thickness

Fields:
- `pricing_mode = formula`
- `formula_type`
- `material_price_id`
- `thickness_cm` or other required product field

Examples of supported formula types:
- `board_by_thickness_cm`
- `fixed_unit_price`
- `pack_price_from_pack_qty`

Important:
Do not build a freeform expression engine.
Use predefined formula types only.

### C. System Template
Use for complex system products made from multiple material price lines.

Fields:
- `pricing_mode = system_template`
- `system_template_id`
- product dimensions / thickness data required by the template

Template lines may use:
- fixed quantity
- product thickness quantity

Do not support arbitrary scripting.
Keep line quantity rules explicit and limited.

---

## Recommended Database Design

### Table: `material_prices`
Suggested columns:
- `id`
- `name`
- `slug`
- `unit_label`
- `unit_price`
- `is_active`
- `notes` nullable
- timestamps

Example rows:
- EPS Standard / cm
- Graphite EPS / cm
- Adhesive / kg
- Mesh / m2
- Primer / kg

### Table: `system_templates`
Suggested columns:
- `id`
- `name`
- `slug`
- `is_active`
- `notes` nullable
- timestamps

### Table: `system_template_items`
Suggested columns:
- `id`
- `system_template_id`
- `material_price_id`
- `label`
- `quantity_type`
- `quantity_value` nullable
- `sort_order`
- timestamps

Allowed `quantity_type` values:
- `fixed`
- `product_thickness_cm`

Rules:
- if `quantity_type = fixed`, use `quantity_value`
- if `quantity_type = product_thickness_cm`, use product thickness field

### Product fields
Prefer adding these columns to `products`:
- `pricing_mode`
- `manual_price` nullable
- `formula_type` nullable
- `material_price_id` nullable
- `system_template_id` nullable
- `thickness_cm` nullable
- `calculated_price` nullable
- `price_calculated_at` nullable
- `price_override_enabled` boolean default false
- `price_override_note` nullable

Optional later:
Create a dedicated override table only if truly needed.

---

## Important Design Rules

### Rule 1: One product = one pricing mode
A product must never depend on hidden fallback priority.

Bad:
- if manual price exists use that
- else if product components exist use that
- else if base recipe exists use that
- else if attribute exists use that

Good:
- product declares `pricing_mode`
- calculator uses exactly that mode

### Rule 2: No generic attribute slug engine
Do not build a system where every quantity depends on free-text attribute slugs everywhere.

Instead, use explicit product fields for the core domain.

For the insulation domain, the most important field is:
- `thickness_cm`

This is clearer and much safer than dynamic slug lookups.

### Rule 3: Keep overrides rare and visible
If a product uses custom override pricing, show it clearly in admin.

Suggested UI warning:
> This product uses custom price override and does not fully follow the shared pricing template.

### Rule 4: Store calculated price
Do not recalculate everything live in every frontend query.

Use:
- `calculated_price`
- `price_calculated_at`

When dependencies change, recalculate affected products.

Benefits:
- faster listing pages
- easier debugging
- consistent frontend behavior
- easier admin diagnostics

---

## Price Calculation Service

Create a dedicated service class:

`app/Services/Pricing/ProductPriceCalculator.php`

Required public methods:

```php
calculate(Product $product): int|float
explain(Product $product): array
recalculateProduct(Product $product): void
recalculateByMaterialPrice(MaterialPrice $materialPrice): int
recalculateBySystemTemplate(SystemTemplate $template): int
```

### `calculate(Product $product)`
Return the final numeric price based on the product pricing mode.

### `explain(Product $product)`
Return a structured explanation array for admin debugging.

Example output:

```php
[
    'pricing_mode' => 'system_template',
    'template' => 'EPS Standard System',
    'lines' => [
        [
            'label' => 'EPS board',
            'unit_price' => 282,
            'quantity' => 10,
            'line_total' => 2820,
            'source' => 'product.thickness_cm',
        ],
        [
            'label' => 'Adhesive',
            'unit_price' => 150,
            'quantity' => 3.5,
            'line_total' => 525,
            'source' => 'fixed',
        ],
    ],
    'final_price' => 4555,
]
```

### `recalculateProduct(Product $product)`
- calculate current price
- save into `calculated_price`
- save `price_calculated_at`

### `recalculateByMaterialPrice(MaterialPrice $materialPrice)`
Find all products affected directly or indirectly and recalculate them.

### `recalculateBySystemTemplate(SystemTemplate $template)`
Recalculate all products using the template.

---

## Admin Panel UX Recommendations (Filament)

### Menu Structure
Use separate admin resources:

1. `Products`
2. `Material Prices`
3. `System Templates`

### Product Form
In the product edit form, show pricing fields conditionally by `pricing_mode`.

#### If `manual`
Show only:
- manual price

#### If `formula`
Show:
- formula type
- material price selector
- thickness cm if relevant

#### If `system_template`
Show:
- system template selector
- thickness cm if relevant

Hide unrelated fields to reduce confusion.

### Product Price Debug Block
Add a read-only section to the product edit page:

**Price Explanation**
- pricing mode
- source material or template
- lines and subtotals
- final calculated price
- last recalculated time

This is extremely important.

### Material Price Edit Action
When a material price changes:
- save the new value
- dispatch recalculation job
- show admin feedback, for example:
  - `127 products recalculated successfully`

---

## Recalculation Flow

### Recommended approach
Use queued jobs for bulk recalculation.

Possible jobs:
- `RecalculateProductPriceJob`
- `RecalculateProductsByMaterialPriceJob`
- `RecalculateProductsBySystemTemplateJob`

### Trigger events
Dispatch recalculation when:
- material price updated
- system template item created/updated/deleted
- product pricing fields updated

### Performance note
Do not do heavy sync recalculation inside every form save if the affected set is large.
Use queue jobs when needed.

---

## Migration Strategy From Current System

We should not try to preserve all complexity.

### Step 1
Review current product categories and classify them into:
- manual
- formula
- system_template

### Step 2
Map current base prices into `material_prices`.

### Step 3
Create shared `system_templates` for the common insulation system packages.

### Step 4
Add new product pricing fields.

### Step 5
Write a one-time migration / command to assign pricing mode and dependencies to existing products.

### Step 6
Run bulk recalculation and compare a sample set manually.

### Step 7
Disable or remove the old fallback logic.

Important:
Do not leave both pricing engines active for long.
That would create confusion and inconsistent results.

---

## What We Explicitly Do NOT Want

Do not implement these in V1:

- generic fallback chains
- freeform pricing expressions
- unlimited attribute slug based rules
- multiple simultaneous price sources per product
- hidden precedence logic
- product-level custom recipe builder for all products

We only want the minimum needed structure for this business domain.

---

## Recommended Implementation Phases

## Phase 1 — Data Model
Tasks:
- create migrations for `material_prices`
- create migrations for `system_templates`
- create migrations for `system_template_items`
- add pricing columns to `products`
- create Eloquent relationships

Acceptance criteria:
- DB structure is clean and explicit
- a product can represent manual / formula / system template pricing

## Phase 2 — Calculator Service
Tasks:
- implement `ProductPriceCalculator`
- implement support for `manual`
- implement support for `formula`
- implement support for `system_template`
- implement `explain()` output

Acceptance criteria:
- calculator returns correct values for known sample products
- explanation output is readable and reliable

## Phase 3 — Filament Admin
Tasks:
- create `MaterialPriceResource`
- create `SystemTemplateResource`
- update `ProductResource`
- conditional pricing fields by pricing mode
- add price explanation block to product page

Acceptance criteria:
- admin can manage material prices and templates without confusion
- product price source is visible immediately

## Phase 4 — Recalculation Jobs
Tasks:
- create bulk recalculation jobs
- trigger them from material/template/product changes
- add success feedback / notifications

Acceptance criteria:
- changing a material price updates all affected products safely
- frontend uses stored `calculated_price`

## Phase 5 — Data Migration From Old System
Tasks:
- audit current products
- map old base prices to new material prices
- map old recipe structures to system templates
- create migration command or admin action
- validate with sample products

Acceptance criteria:
- migrated sample products match expected prices
- old logic can be removed safely

## Phase 6 — Cleanup
Tasks:
- remove dead code
- remove old fallback logic
- remove old admin confusion
- add basic documentation for admins

Acceptance criteria:
- only one pricing system remains active
- codebase is easier to maintain

---

## Suggested Eloquent Relationships

### Product
- belongsTo `materialPrice`
- belongsTo `systemTemplate`

### SystemTemplate
- hasMany `items`

### SystemTemplateItem
- belongsTo `systemTemplate`
- belongsTo `materialPrice`

---

## Suggested Enum Values

### `pricing_mode`
- `manual`
- `formula`
- `system_template`

### `formula_type`
- `board_by_thickness_cm`
- `fixed_unit_price`
- `pack_price_from_pack_qty`

### `quantity_type`
- `fixed`
- `product_thickness_cm`

Keep the allowed values small and intentional.

---

## Suggested Sample Business Cases

### Case 1 — Manual price product
A ready-made accessory uses fixed price.

Expected:
- product price comes from `manual_price`

### Case 2 — EPS board 10 cm
- `pricing_mode = formula`
- `formula_type = board_by_thickness_cm`
- material price = EPS Standard = 282
- thickness = 10

Expected price:
- `282 * 10 = 2820`

### Case 3 — EPS system 10 cm
System template lines:
- EPS board => thickness-based
- Adhesive => 3.5 fixed
- Mesh => 4.5 fixed
- Primer => 2.0 fixed

Expected price:
- `282 * 10 + 150 * 3.5 + 180 * 4.5 + 200 * 2.0 = 4555`

These sample cases should be covered by tests.

---

## Testing Requirements

Create automated tests for:

1. manual pricing
2. formula pricing by thickness
3. system template pricing
4. recalculation after material price update
5. explanation output structure
6. product with missing required pricing data should fail safely

Also test admin-side validation:
- manual mode requires manual price
- formula mode requires material price + formula type
- system template mode requires template id

---

## Validation Rules

Examples:

### Manual mode
Require:
- `manual_price`

### Formula mode
Require:
- `formula_type`
- `material_price_id`
- domain-specific required fields, for example `thickness_cm`

### System template mode
Require:
- `system_template_id`
- domain-specific required fields if template depends on them

Prevent incomplete product pricing setups.

---

## Deliverables Expected From Claude Code

Claude Code should implement the following:

1. migrations
2. models and relationships
3. enum usage where appropriate
4. `ProductPriceCalculator` service
5. Filament resources for material prices and system templates
6. product form refactor with conditional pricing UI
7. price explanation admin block
8. queued recalculation jobs
9. automated tests
10. removal plan for the legacy pricing logic

---

## Final Guiding Principle

This pricing system must be:

- explicit
- debuggable
- safe for daily price changes
- understandable by admins
- easy to extend in a controlled way

We prefer a smaller, clearer, business-specific system over a generic but confusing rule engine.
