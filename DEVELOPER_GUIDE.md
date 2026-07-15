# VMFS Cloud — Developer Guide

> Comprehensive reference for developers joining this project.  
> Last updated: 2026-05-13

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Tech Stack](#2-tech-stack)
3. [Architecture](#3-architecture)
4. [Local Setup](#4-local-setup)
5. [Code Conventions](#5-code-conventions)
6. [User Roles & Access](#6-user-roles--access)
7. [Admin Panel Modules](#7-admin-panel-modules)
8. [REST API](#8-rest-api)
9. [Database Schema Overview](#9-database-schema-overview)
10. [Key Models & Relationships](#10-key-models--relationships)
11. [Enums Reference](#11-enums-reference)
12. [Background & Queue](#12-background--queue)
13. [File Storage](#13-file-storage)
14. [Testing](#14-testing)
15. [Known Gotchas](#15-known-gotchas)
16. [Implementation Progress](#16-implementation-progress)

---

## 1. Project Overview

**VMFS Cloud** is a multi-tenant SaaS platform for remotely managing VMFS-brand vending machines. It serves two types of clients:

- **Web browsers** — operators access a full-featured admin panel at `/admin`
- **Flutter kiosk app** — physically installed on each machine, consumes a JSON REST API

Core capabilities include:

- Real-time inventory and sales monitoring across all machines
- Product, pricing, coupon, and lottery management
- Configurable on-screen advertising per machine or machine group
- Wallet top-ups, renewal billing, and financial reporting
- Work order ticketing for field maintenance
- Brand customization (logos, themes, footer content)
- Notification configuration for operational alerts

---

## 2. Tech Stack

| Layer | Technology | Version |
|---|---|---|
| Language | PHP | ^8.3 |
| Framework | Laravel | ^13.0 |
| Admin panel | Filament | ^5.0 |
| Reactive UI | Livewire | ^4.0 |
| CSS | Tailwind CSS | ^4.0 |
| Frontend bundler | Vite | — |
| Database | MySQL | 8.x recommended |
| Queue / Cache / Session | Database driver | — |
| File storage | Local disk (`public`) | S3-compatible optional |
| Package management | Composer + npm | — |
| Dev tools | Laravel Pail, Laravel Pint, PHPUnit 12 | — |

---

## 3. Architecture

```
┌──────────────────────────────────────────────────────────────┐
│                        Browser (Operator)                    │
│                   https://vmfs.sm-vending.com/admin          │
└───────────────────────────┬──────────────────────────────────┘
                            │ Livewire / Filament
┌───────────────────────────▼──────────────────────────────────┐
│                         Laravel 13                           │
│                                                              │
│  ┌─────────────────────┐   ┌──────────────────────────────┐  │
│  │   Filament 5 Panel  │   │     REST API  /api/v1/       │  │
│  │   /admin/*          │   │     JSON responses           │  │
│  │                     │   │                              │  │
│  │  Resources, Pages,  │   │  Public endpoints (throttle) │  │
│  │  Widgets, Actions   │   │  Admin endpoints (Bearer)    │  │
│  └─────────────────────┘   └──────────────┬───────────────┘  │
│                                           │                  │
│                      MySQL (vms_cloud)    │                  │
└───────────────────────────────────────────┼──────────────────┘
                                            │ HTTP / JSON
┌───────────────────────────────────────────▼──────────────────┐
│               Flutter Kiosk App (on each machine)            │
│  Calls API on boot, on each sale, and on every ad rotation   │
└──────────────────────────────────────────────────────────────┘
```

### Panel Provider

`app/Providers/Filament/AdminPanelProvider.php` registers:
- Auto-discovered resources from `app/Filament/Admin/Resources/`
- Auto-discovered pages from `app/Filament/Admin/Pages/`
- Auto-discovered widgets from `app/Filament/Admin/Widgets/`
- Static navigation placeholder items via `AdminNavigationItems::definitions()`
- Navigation group ordering

---

## 4. Local Setup

### Prerequisites

- PHP 8.3+
- MySQL 8.x
- Node.js (see `.nvmrc` for version)
- Composer

### Steps

```bash
# 1. Clone and install dependencies
composer install
npm install

# 2. Copy environment file
cp .env.example .env
php artisan key:generate

# 3. Configure your database in .env
#    DB_DATABASE=vms_cloud
#    DB_USERNAME=root
#    DB_PASSWORD=yourpassword

# 4. Run migrations and seed default data
php artisan migrate
php artisan db:seed

# 5. Link public storage (required for product/brand images)
php artisan storage:link

# 6. Build frontend assets
npm run build

# 7. Start the development server (all services in one command)
composer run dev
```

`composer run dev` starts Laravel, queue worker, Pail log viewer, and Vite simultaneously.

### Default Test Credentials

| Field | Value |
|---|---|
| Email | `test@example.com` |
| Password | `password` |
| Account | `testuser` |
| Role | Admin |

### Required Environment Variables

| Key | Description |
|---|---|
| `DB_*` | MySQL connection settings |
| `LOTTERY_MANAGEMENT_API_TOKEN` | Bearer token required by all `/api/v1/admin/*` endpoints and product lottery management endpoints. Must be set and shared with the Flutter app. |
| `APP_URL` | Must match the host/port you open in the browser. Affects public disk image URLs. |

---

## 5. Code Conventions

All PHP files follow these rules — **check sibling files before creating new ones**:

- `declare(strict_types=1)` at the top of every file
- Classes are `final` by default
- PHP 8 attribute syntax for Eloquent: `#[Fillable([...])]`, `#[Hidden([...])]`
- No unnecessary comments — only write one when the **why** is non-obvious
- No feature flags, no backwards-compatibility shims
- Validate only at system boundaries (user input, external APIs)
- `composer run test` must pass before committing

### Filament Resource Pattern

Every Resource lives in its own directory:
```
app/Filament/Admin/Resources/
  SomethingResource/
    SomethingResource.php      ← Resource class (form + table + pages)
    Pages/
      ManageSomethings.php     ← Usually ManageRecords (create/edit/delete on one page)
```

The `ManageRecords` page always overrides `getDefaultActionSchemaResolver()` to force single-column layout in modals:

```php
public function getDefaultActionSchemaResolver(Action $action): ?Closure
{
    return match (true) {
        $action instanceof CreateAction, $action instanceof EditAction
            => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(1)),
        $action instanceof ViewAction
            => fn (Schema $schema): Schema => $this->infolist($this->form(...)),
        default => parent::getDefaultActionSchemaResolver($action),
    };
}
```

### Single-row Settings Pages

Pages like `BrandSettings` and `NotificationConfiguration` use a single-row model pattern:

```php
// Model: always returns/creates the one row
public static function current(): self
{
    return self::$currentCache ??= self::query()->first() ?? self::query()->create([...defaults]);
}

public static function forgetCurrentCache(): void
{
    self::$currentCache = null;
}
```

### Report Widgets

Report widgets (`DeviceIncomeTable`, `ProductIncomeTable`, etc.) have `canView(): false` so they don't appear on the main dashboard — they're only mounted explicitly by their report page via `getFooterWidgets()`.

---

## 6. User Roles & Access

Defined in `App\Enums\UserRole`:

| Role | Description |
|---|---|
| `super_admin` | Full system access |
| `admin` | Full operational access |
| `agency` | Manages a subset of machines |
| `operator` | Field-level access |
| `customer` | End-user (not yet active in panel) |

Access to the Filament panel is controlled by `User::canAccessPanel()` (via `FilamentUser` contract). Individual resource visibility can be restricted via `canViewAny()`, `canCreate()`, `canEdit()`, `canDelete()` on each Resource.

---

## 7. Admin Panel Modules

### Navigation Groups & Their Modules

#### Machines
| Item | Type | Description |
|---|---|---|
| Machine list | Resource | CRUD for machines. Table shows: number, name, group, user, enabled status, **inventory badge** (Stocked/Low/Out of stock), **online/offline badge** (based on `last_seen_at` within 15 min) |
| Machine groups | Resource | Group machines by location/zone; each group can have a default ad group |
| Finance groups | Resource | Group machines for financial reporting |
| Machine label groups | Resource | Tag-style grouping for machines |
| Machine alarms | Resource | Create/acknowledge machine alerts with severity levels |
| Machine map | Page | Geographic map view of all machines with lat/lng coordinates |

#### Products
| Item | Type | Description |
|---|---|---|
| Product list | Resource | Full product catalog. Supports images, 3D models (.glb/.fbx), media expansions, specs, tags, pricing |
| Product lotteries | Resource | Lottery/raffle system tied to products. Each lottery has prize tiers with weights, generates unique redeemable codes. Has a `public_draw_token` for the Flutter kiosk draw endpoint |
| Coupons | Resource | Discount codes (fixed $ or %). Assigned to machine groups. Auto-generates codes on creation |
| Categories | Resource (`SpecificationResource`) | Product categories with selling type (by piece, by weight, etc.) |
| Product types | Resource (`ProductTypeResource`) | Simple type labels (Vape, Beverage, Snack, etc.) |
| Product tags | Resource (`ProductTagResource`) | Tags like Featured, New, On sale |

#### Advertising
| Item | Type | Description |
|---|---|---|
| Advertisement list | Resource | Images or videos (max 100 MB). Dimensions guide: Portrait 1080×1920 (full), 1080×440 (top); Landscape 1920×1080 |
| Advertisement groups | Resource | Bundles ads into slots: Screensaver, Top, External screen. Groups are assigned to machines or machine groups |
| Advertisement tags | Resource | Optional tagging system for ads |

#### Sales
| Item | Type | Description |
|---|---|---|
| Order list | Resource | All vending machine transactions. Read-only (created by Flutter API). Filter by machine, status, payment method, date range |
| Refund records | Page | Filtered view of orders with `status = refunded` |
| Recharge record | Nav link | Links to Wallet → Recharge Records (same data, accessible from Sales context) |

#### Reports
All report pages embed table widgets with aggregated data. All support date range filters.

| Item | Data |
|---|---|
| Device income | Revenue grouped by machine (`orders JOIN machines`) |
| Product income | Revenue grouped by product name |
| User income | Revenue grouped by user (`orders JOIN machines JOIN users`) |
| Date income | Revenue grouped by calendar day. Also filterable by machine |
| Statistics | Key KPI stats + user sales ranking |
| Data Dashboard | All charts: Revenue Trend, Sales Mix, Slot Inventory, New Device Trend, Recent Orders |

#### Applications
Placeholder modules for future integrations:
- Add Applications / Applications List (module marketplace)

#### System maintenance
| Item | Type | Description |
|---|---|---|
| Information storage records | Resource | GDPR/consent records. Supports Points or Times rule types, IC card numbers |
| Push records | Resource | Log of push notifications sent to machines |
| Work orders | Resource | Full work order system: issue type, priority, status, attachments, rating |
| My work orders | Resource | Same as Work orders but scoped to the authenticated user |

#### System
| Item | Type | Description |
|---|---|---|
| Users | Resource | Full user management with roles, timezone, contact emails |
| Notification configuration | Page | Toggle email alerts: account, inventory shortage, equipment offline, slot failure, network anomaly |

#### Wallet
| Item | Type | Description |
|---|---|---|
| Recharge wallet | Page | Operator initiates a wallet top-up (adds to `wallet_recharge_pending`) |
| Recharge records | Resource | History of all wallet recharge transactions. Read-only |
| Collection account config | Page | Configure payment gateway credentials. Supports multiple gateways via dynamic `PaymentGatewayFormBuilder` |
| Renewal center | Page | Equipment renewal management — lists expiring equipment, accepts payments (PayPal, Stripe, offline, balance deduction) |

#### Brand
| Item | Type | Description |
|---|---|---|
| Brand | Page (`BrandSettings`) | Upload logos, background images, startup animation (.zip), set webpage title and footer HTML |

---

## 8. REST API

Base path: `/api/v1/`  
All responses are JSON. Throttle limits apply per endpoint.

### Public Endpoints (no authentication)

| Method | Endpoint | Rate limit | Description |
|---|---|---|---|
| `GET` | `machines/{machineNo}/slots` | 120/min | Returns all active slots with product info and stock for a machine. Also updates `machines.last_seen_at`. |
| `GET` | `machines/{machineNo}/advertisements` | 60/min | Returns ads organized by slot (screensaver, top, external_screen). Also updates `machines.last_seen_at`. |
| `POST` | `lottery-codes/lookup` | 60/min | Validates a lottery code and returns prize info |
| `POST` | `product-lottery-draw/{token}` | 60/min | Public draw using `public_draw_token`. Returns prize, code, and `lineNumber` for physical dispensing |
| `POST` | `dispense` | 60/min | Records physical dispense result. Creates an Order, updates slot stock, marks code as dispensed |

### Admin Endpoints (Bearer token: `LOTTERY_MANAGEMENT_API_TOKEN`)

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `admin/machines/{machineNo}/dashboard` | Operational summary: today's orders, revenue, inventory stats, active lotteries |
| `GET` | `admin/machines/{machineNo}/slots` | Full slot list including inactive and empty slots |
| `PATCH` | `admin/slots/{id}` | Update slot settings (product, stock, price, active/fault status) |
| `GET` | `admin/machines/{machineNo}/orders` | Paginated order history for a machine |
| `GET` | `admin/products` | Product catalog list |
| `POST` | `admin/products` | Create a product |
| `PATCH` | `admin/products/{id}` | Update a product |

### Online/Offline Detection

Machines are considered **online** if `machines.last_seen_at` was updated within the last **15 minutes**. The `GET /slots` and `GET /advertisements` endpoints automatically call `Machine::touchLastSeen()` on each request from the kiosk.

---

## 9. Database Schema Overview

Key tables and their purpose:

| Table | Purpose |
|---|---|
| `users` | All system users. Has wallet balance fields, role, timezone, contact emails |
| `machines` | Vending machines. Has `last_seen_at` for online detection |
| `machine_groups` | Groups of machines for bulk config |
| `machine_slots` | Physical slots inside each machine (line_number, stock, product, price, fault) |
| `machine_alarms` | Alerts for machines |
| `machine_label_groups` | Label-based groupings (pivot: `machine_label_group_machine`) |
| `finance_groups` | Financial grouping of machines |
| `products` | Product catalog (name, price, images, specification_id, product_tag_id) |
| `specification_types` | Product type labels (standalone, no FK to products) |
| `specifications` | Product categories/subcategories with `specification_type` enum |
| `product_tags` | Product tags (FK: `products.product_tag_id`) |
| `product_lotteries` | Lottery configurations tied to products |
| `product_lottery_prizes` | Prize tiers per lottery (weight, amount, line_number) |
| `product_lottery_codes` | Individual generated codes with dispense tracking |
| `orders` | Transaction records created by the dispense API |
| `coupons` | Coupon definitions |
| `coupon_codes` | Individual generated coupon codes |
| `coupon_machine_group` | Pivot: coupon ↔ machine_group assignment |
| `advertisements` | Ad media files with metadata |
| `advertisement_groups` | Named bundles of ads organized by slot |
| `advertisement_group_advertisement` | Pivot: group ↔ ad, with `slot` and `sort_order` |
| `advertisement_tags` | Tagging for ads |
| `advertisement_advertisement_tag` | Pivot: ad ↔ tag |
| `brand_settings` | Single-row table for branding config |
| `notification_settings` | Single-row table for alert toggles and email |
| `payment_collection_configs` | Payment gateway credentials per gateway slug |
| `recharge_records` | Wallet recharge transaction history |
| `renewal_equipments` | Equipment subject to renewal billing |
| `renewal_histories` | Renewal payment history |
| `work_orders` | Maintenance tickets |
| `push_records` | Push notification log |
| `information_storage_records` | GDPR/IC card consent records |
| `sessions` | Laravel session storage (database driver) |
| `cache` | Laravel cache storage (database driver) |
| `jobs` | Laravel queue storage (database driver) |

---

## 10. Key Models & Relationships

```
User
 └── hasMany Machine (via user_id)
 └── hasMany WorkOrder

Machine
 ├── belongsTo User
 ├── belongsTo MachineGroup
 ├── belongsTo FinanceGroup
 ├── belongsTo AdvertisementGroup  (individual override)
 ├── belongsToMany MachineLabelGroup
 ├── hasMany MachineSlot
 └── hasMany MachineAlarm

MachineGroup
 ├── belongsTo AdvertisementGroup  (default for group)
 ├── hasMany Machine
 └── belongsToMany Coupon          (pivot: coupon_machine_group)

MachineSlot
 ├── belongsTo Machine
 └── belongsTo Product

Product
 ├── belongsTo Specification       (category)
 ├── belongsTo ProductTag
 └── hasMany ProductLottery (indirect — through machine_no)

ProductLottery
 ├── belongsTo Product
 ├── hasMany ProductLotteryPrize
 └── hasMany ProductLotteryCode

ProductLotteryCode
 ├── belongsTo ProductLottery
 ├── belongsTo ProductLotteryPrize
 └── hasOne Order

Order
 ├── belongsTo ProductLotteryCode
 └── belongsTo MachineSlot

Coupon
 ├── hasMany CouponCode
 └── belongsToMany MachineGroup

AdvertisementGroup
 ├── belongsToMany Advertisement   (pivot: slot, sort_order)
 ├── hasMany Machine (direct override)
 └── hasMany MachineGroup (default)

Advertisement
 ├── belongsToMany AdvertisementGroup
 └── belongsToMany AdvertisementTag
```

---

## 11. Enums Reference

All enums implement `HasLabel` for Filament display.

| Enum | Values | Used in |
|---|---|---|
| `UserRole` | super_admin, admin, agency, operator, customer | `users.role` |
| `UserRegistrationMethod` | admin, self_registered | `users.registration_method` |
| `AdvertisementType` | image, video | `advertisements.type` |
| `AdvertisementGroupSlot` | screensaver, top, external_screen | Pivot `advertisement_group_advertisement.slot` |
| `SpecificationSellingType` | by_the_piece, by_weight, ... | `specifications.specification_type` |
| `CouponDiscountType` | fixed_amount, percentage | `coupons.coupon_type` |
| `CouponGenerationRule` | letters_and_numbers, numbers_only, ... | `coupons.generation_rule` |
| `CouponDistributionRule` | — | `coupons.distribution_rule` |
| `WorkOrderStatus` | unprocessed, in_progress, completed, closed | `work_orders.status` |
| `WorkOrderPriority` | low, normal, high, urgent | `work_orders.priority` |
| `WorkOrderIssueType` | machine_issue, network_issue, ... | `work_orders.issue_type` |
| `WorkOrderReportingStatus` | none, reported, resolved | `work_orders.reporting_status` |
| `PushMethod` | — | `push_records.push_method` |
| `RenewalPayType` | — | `renewal_histories.pay_type` |
| `RenewalProgress` | — | `renewal_histories.renewal_progress` |
| `InformationStorageCollectionMethod` | member_card, ... | `information_storage_records.collection_method` |
| `InformationStorageRuleType` | points, times | `information_storage_records.rule_type` |

---

## 12. Background & Queue

The queue connection is `database` (table: `jobs`). Start the worker with:

```bash
php artisan queue:listen --tries=1 --timeout=0
```

Or via `composer run dev` which starts it automatically.

Queued work currently includes coupon/lottery code generation when large batches are created.

---

## 13. File Storage

The default disk is `local`. For public files (product images, brand assets, advertisements) the `public` disk is used, backed by `storage/app/public/` symlinked to `public/storage/`.

**Run `php artisan storage:link` after fresh setup.**

In local development, `SyncPublicDiskUrlForLocalRequests` middleware dynamically adjusts the public disk URL to match the current request host/port, so `APP_URL` mismatches don't break image rendering.

Key upload directories:
| Directory | Contents |
|---|---|
| `products/main/` | Product main images |
| `products/media/` | Product media expansion files |
| `advertisements/media/` | Ad images and videos |
| `brand/` | Logo, background, icon files |
| `brand/animations/` | Startup animation ZIP files |
| `work-orders/` | Work order photo/video attachments |

---

## 14. Testing

```bash
# Run all tests
composer run test

# Or directly
php artisan test
```

Test coverage includes:
- Feature tests for every Filament resource page (create, edit, delete, list)
- API feature tests for lottery code lookup and draw
- Unit tests for coupon QR renderer and payment gateway form builder

Test files live in `tests/Feature/` and `tests/Unit/`.

The test suite uses `RefreshDatabase` — a fresh SQLite or MySQL test database is used per run (check `phpunit.xml` for config).

---

## 15. Known Gotchas

### `specification_type_id` was dropped from `products`

An early migration (`2026_04_01_200000_add_specification_id_to_products_and_drop_specification_type_id`) replaced `specification_type_id` with `specification_id` on the `products` table. The `SpecificationType` model no longer has a working `products()` relationship — it was cleaned up. Do not attempt to add `->counts('products')` on `SpecificationType` resources.

### Machine column naming: `machine_number` vs `machine_no`

- `machines` table column: **`machine_number`**
- `orders` table column (denormalized): **`machine_no`**

When joining these tables always use:
```sql
orders.machine_no = machines.machine_number
```
And reference `machines.machine_number` (not `machines.machine_no`) in SELECTs and COUNTs.

### `last_seen_at` is set by the API, not the admin panel

`Machine::touchLastSeen()` is called by `MachineSlotController` and `AdvertisementController` on each kiosk request. It uses `$this->timestamps = false` to avoid updating `updated_at`. The online threshold is hardcoded to **15 minutes** in `Machine::isOnline()`.

### Report widgets must have `canView(): false`

All widgets in `app/Filament/Admin/Widgets/` are auto-discovered and will appear on the main dashboard unless they override `canView()`. Report-specific widgets (`DeviceIncomeTable`, `ProductIncomeTable`, `UserIncomeTable`, `DateIncomeTable`, `RefundRecordsTable`) all return `false` from `canView()` and are only mounted explicitly by their report page.

### Lottery codes and orders flow

1. Flutter calls `POST /api/v1/lottery-codes/lookup` to validate a code
2. Flutter calls `POST /api/v1/product-lottery-draw/{token}` (public draw) to get a prize and `lineNumber`
3. Flutter sends the dispense command to the physical machine via serial
4. Flutter calls `POST /api/v1/dispense` with the result — this creates the `Order` record and decrements slot stock

### Payment gateways are config-driven

`CollectionAccountConfig` reads gateway definitions from `config('payment_gateways')`. The form schema for each gateway is built dynamically by `PaymentGatewayFormBuilder`. To add a new payment gateway, add it to the config file and implement its form schema builder.

### `AdminNavigationItems` manages placeholder nav entries

Modules not yet implemented as real Filament pages/resources appear as nav items defined in `AdminNavigationItems::definitions()`. They redirect to `ModulePlaceholder` with a `?label=` query string that becomes the page title. When a real page/resource is built for that item, **remove the corresponding placeholder entry from `AdminNavigationItems`** to avoid duplicates.

---

## 16. Implementation Progress

See [`PROGRESS.md`](./PROGRESS.md) for the full gap analysis against the VMFS Software Manual v1.0 and the status of each feature (✅ done / ❌ pending).

Major areas still pending as of 2026-05-13:
- **Theme Management** (Section 10 of manual) — no models, migrations, or UI exist yet. Requires full design.
- **Applications marketplace modules** — IC Card, BankID, Machine Temperature, Age Verification, etc. All are external integrations shown as placeholders.
- **Machines — Online/Offline via real heartbeat** — currently relies on kiosk API calls updating `last_seen_at`. If the machine is idle (no sales, no ad rotation), the status may drift to Offline even if connected.
