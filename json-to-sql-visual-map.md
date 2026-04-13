# CREATY JSON to SQL Visual Map

Generated: 2026-04-13

Scope:
- Covers all JSON-backed data stores found in `config/` and `config/archives/`.
- Excludes MySQL account tables (`customer_accounts`, `admin_accounts`, `staff_accounts`) as requested.
- This is a visualization map for migration/design, not an exact SQL migration script.

## 1) JSON Stores Found (Top to Bottom)

| JSON File | Suggested SQL Table | Current JSON Shape | Primary Key Candidate |
| --- | --- | --- | --- |
| `config/products.json` | `products` | object map keyed by product key | `product_key` (from object key) |
| `config/brands.json` | `product_brands` | array of strings | `brand_name` |
| `config/categories.json` | `product_categories` | array of strings | `category_name` |
| `config/event_packages.json` | `event_packages` | object map keyed by package key | `package_key` (from object key) |
| `config/equipment_inventory.json` | `equipment_inventory_models` + `equipment_inventory_units` | object map with nested units array | `product_key` + (`product_key`,`serial`) |
| `config/equipment_statuses.json` | `equipment_statuses` | array of strings | `status_token` |
| `config/customer_orders.json` | `customer_orders` + `customer_order_items` | array of order objects with nested items | `id` + derived item key |
| `config/customer_notifications.json` | `customer_notifications` | array of notification objects | `id` |
| `config/message_notifications.json` | `message_notifications` + `message_notification_attachments` | array with polymorphic payload | `id` |
| `config/customer_gcash_profiles.json` | `customer_gcash_profiles` | array of customer gcash records | `customer_id` |
| `config/gcash_qr.json` | `gcash_qr_settings` | single object | single row (`id=1`) |
| `config/customer_terms.json` | `customer_terms` | single object | single row (`id=1`) |
| `config/archives/products_archived.json` | `archived_products` | array of archive entries with product snapshot | `archiveKey` |
| `config/archives/equipment_units_archived.json` | `archived_equipment_units` | array of archived unit entries | `archiveKey` |
| `config/archives/how_it_works_archived.json` | `archived_how_it_works` | array | `archiveKey` |
| `config/archives/promo_banners_archived.json` | `archived_promo_banners` | array | `archiveKey` |

## 2) FK-Style Connection Map

| From | To | Type | Notes |
| --- | --- | --- | --- |
| `products.brand` | `product_brands.brand_name` | FK-like | Enforced by brand normalization helpers |
| `products.category` | `product_categories.category_name` | FK-like | Enforced by category normalization helpers |
| `products.recommendations[]` | `products.product_key` | self-FK (array) | Product recommendations point to product keys |
| `equipment_inventory_models.product_key` | `products.product_key` | FK-like | Inventory is keyed by product key |
| `equipment_inventory_units.product_key` | `products.product_key` | FK-like | Child rows from `units[]` |
| `equipment_inventory_units.status` | `equipment_statuses.status_token` | FK-like | Status token list comes from statuses JSON |
| `customer_orders.customer_id` | `customers.id` | External FK | Customer account table is MySQL (excluded) |
| `customer_order_items.order_id` | `customer_orders.id` | FK | Derived from order `items[]` |
| `customer_order_items.product_key` | `products.product_key` | Conditional FK | Applies when `item_type='camera'` |
| `customer_order_items.event_package_key` | `event_packages.package_key` | Derived FK | For `item_type='event-package'` via `item_id='event-{package_key}'` |
| `customer_order_item_assigned_units.(product_key,serial)` | `equipment_inventory_units.(product_key,serial)` | Composite FK-like | Assignment generated from inventory sync |
| `customer_notifications.customer_id` | `customers.id` | External FK | Customer account table excluded |
| `customer_notifications.order_id` | `customer_orders.id` | FK-like | Order-status and delivery notifications |
| `message_notifications.payload.order_id` | `customer_orders.id` | Conditional FK | Applies when `type='order'` |
| `customer_gcash_profiles.customer_id` | `customers.id` | External FK | One profile per customer |
| `customer_gcash_profiles.customer_id` | `customer_orders.customer_id` | Logical FK | Payment profile used during order receipt flow |
| `archived_equipment_units.productKey` | `products.product_key` | Weak FK | Can reference removed products |
| `archived_equipment_units.productArchiveKey` | `archived_products.archiveKey` | Optional FK | Present when tied to product archive event |

## 3) SQL-Style Tables (Tabularized)

### 3.1 Product Catalog Domain

#### `product_brands` (from `brands.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `brand_name` | VARCHAR(120) | PK | Canon, Fuji, Nikon, Sony, etc. |

#### `product_categories` (from `categories.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `category_name` | VARCHAR(120) | PK | Photography, Videography, etc. |

#### `products` (from `products.json` object map)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK | Derived from JSON object key (example: `canon-700d`) |
| `brand` | VARCHAR(120) | FK-like | -> `product_brands.brand_name` |
| `name` | VARCHAR(160) |  | Product name |
| `skillLevel` | VARCHAR(60) |  | Primary skill level |
| `skillLevels` | JSON |  | Optional multi-skill array |
| `category` | VARCHAR(120) | FK-like | -> `product_categories.category_name` |
| `price` | DECIMAL(10,2) |  | Stored as string in JSON |
| `discountPercent` | INT |  | 0..95 in normalizers |
| `spec1` | VARCHAR(255) |  | Quick spec line |
| `spec2` | VARCHAR(255) |  | Quick spec line |
| `tagline` | VARCHAR(255) |  | Product tagline |
| `cameraImage` | VARCHAR(500) |  | Asset path |
| `captureSlides` | JSON |  | Array of strings |
| `specs` | JSON |  | Object of grouped spec arrays |
| `recommendations` | JSON |  | Array of recommended product keys |
| `informationImages` | JSON |  | Optional array of image paths |

#### `product_recommendations` (normalized child of `products.recommendations[]`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK/FK | Source product |
| `recommended_product_key` | VARCHAR(120) | PK/FK | Target product |
| `position` | INT | PK | Array index order |

#### `event_packages` (from `event_packages.json` object map)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `package_key` | VARCHAR(120) | PK | Derived from JSON object key |
| `title` | VARCHAR(180) |  | Package title |
| `price` | DECIMAL(10,2) |  | Stored as string in JSON |
| `discountPercent` | INT |  | 0..95 |
| `folder` | VARCHAR(40) |  | Folder code (`0000`, etc.) |
| `thumbnail_images` | JSON |  | Array of image paths |
| `archived` | BOOLEAN |  | Soft archive flag |
| `archivedAt` | DATETIME |  | Empty if not archived |

#### `event_package_thumbnail_images` (normalized child)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `package_key` | VARCHAR(120) | PK/FK | -> `event_packages.package_key` |
| `position` | INT | PK | Array order |
| `image_path` | VARCHAR(500) |  | Thumbnail path |

### 3.2 Inventory Domain

#### `equipment_statuses` (from `equipment_statuses.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `status_token` | VARCHAR(50) | PK | available, maintenance, in-use, retired, etc. |

#### `equipment_inventory_models` (from `equipment_inventory.json` object map)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK/FK | -> `products.product_key` |
| `timesUsed` | INT |  | Usage metric |
| `nextSerial` | INT |  | Next generated unit serial |

#### `equipment_inventory_units` (normalized from `equipment_inventory.json[*].units[]`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK/FK | -> `products.product_key` |
| `serial` | INT | PK | Unit serial per model |
| `status` | VARCHAR(50) | FK-like | -> `equipment_statuses.status_token` |

### 3.3 Orders and Payment Domain

#### `customer_orders` (from `customer_orders.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | VARCHAR(64) | PK | Example: `ord-...` |
| `customer_id` | VARCHAR(64) | FK (external) | -> MySQL customers table |
| `customer_name` | VARCHAR(180) |  | Snapshot at booking time |
| `customer_email` | VARCHAR(180) |  | Snapshot at booking time |
| `status` | VARCHAR(40) |  | Display label (`Pending`, `For Return`, etc.) |
| `receive_date` | DATE |  | |
| `receive_time` | TIME |  | Hour slot |
| `return_date` | DATE |  | |
| `return_time` | TIME |  | |
| `place` | VARCHAR(255) |  | Used for meetup flows |
| `receiving_method` | ENUM-like |  | pickup, meetup, delivery |
| `returning_method` | ENUM-like |  | pickup, meetup, delivery |
| `courier` | ENUM-like |  | lalamove, grab-express, lbc, j-and-t, self-booked |
| `valid_id_path` | VARCHAR(500) |  | Required for delivery routes |
| `valid_id_uploaded_at` | DATETIME |  | |
| `selfie_with_id_path` | VARCHAR(500) |  | Required for delivery routes |
| `selfie_with_id_uploaded_at` | DATETIME |  | |
| `cancel_reason` | VARCHAR(500) |  | Required for cancel/refund statuses |
| `canceled_by` | ENUM-like |  | admin, customer, system |
| `payment_method` | ENUM-like |  | gcash, cash-pickup, cash-meetup |
| `payment_receipt_path` | VARCHAR(500) |  | GCash receipt path |
| `payment_receipt_uploaded_at` | DATETIME |  | |
| `receive_delivery_status` | ENUM-like |  | not-required, waiting-proof, in-transit, closed |
| `receive_delivery_receipt_path` | VARCHAR(500) |  | |
| `receive_delivery_receipt_uploaded_at` | DATETIME |  | |
| `receive_delivery_receipt_uploaded_by` | ENUM-like |  | admin, customer, system |
| `receive_delivery_reference` | VARCHAR(120) |  | Tracking/reference |
| `receive_delivery_notes` | VARCHAR(500) |  | |
| `receive_delivery_closed_at` | DATETIME |  | |
| `receive_delivery_closed_by` | ENUM-like |  | admin, customer, system |
| `return_delivery_status` | ENUM-like |  | not-required, waiting-customer-proof, in-transit, closed |
| `return_delivery_receipt_path` | VARCHAR(500) |  | |
| `return_delivery_receipt_uploaded_at` | DATETIME |  | |
| `return_delivery_receipt_uploaded_by` | ENUM-like |  | admin, customer, system |
| `return_delivery_reference` | VARCHAR(120) |  | Tracking/reference |
| `return_delivery_notes` | VARCHAR(500) |  | |
| `return_delivery_closed_at` | DATETIME |  | |
| `return_delivery_closed_by` | ENUM-like |  | admin, customer, system |
| `receive_handover_confirmed_at` | DATETIME |  | Pickup/meetup handover confirmation |
| `receive_handover_confirmed_by` | ENUM-like |  | admin, customer, system |
| `refund_proof_path` | VARCHAR(500) |  | Required when status becomes refunded |
| `refund_proof_uploaded_at` | DATETIME |  | |
| `created_at` | DATETIME |  | Creation timestamp |

#### `customer_order_items` (normalized child of `customer_orders.items[]`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `order_id` | VARCHAR(64) | PK/FK | -> `customer_orders.id` |
| `item_index` | INT | PK | Position inside `items[]` |
| `name` | VARCHAR(200) |  | Item label |
| `qty` | INT |  | Quantity |
| `days` | INT |  | Rental days |
| `item_id` | VARCHAR(160) |  | Example `camera-canon-700d`, `event-wedding` |
| `item_type` | VARCHAR(60) |  | camera, event-package, etc. |
| `product_key` | VARCHAR(120) | FK-like | Camera link to `products.product_key` |
| `event_package_key` | VARCHAR(120) | Derived FK-like | Derived when `item_type='event-package'` and `item_id` starts with `event-` |

#### `customer_order_item_assigned_units` (normalized child of assignment arrays)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `order_id` | VARCHAR(64) | PK/FK | -> `customer_orders.id` |
| `item_index` | INT | PK/FK | -> `customer_order_items.item_index` |
| `product_key` | VARCHAR(120) | FK-like | camera product |
| `serial` | INT | PK | Unit serial |
| `unit_id` | VARCHAR(120) |  | Computed model-serial id |

### 3.4 Notifications Domain

#### `customer_notifications` (from `customer_notifications.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | VARCHAR(80) | PK | `cust-notif-...` |
| `customer_id` | VARCHAR(64) | FK (external) | -> MySQL customers table |
| `type` | VARCHAR(60) |  | Usually `order-status` |
| `order_id` | VARCHAR(64) | FK-like | -> `customer_orders.id` |
| `status_token` | VARCHAR(80) |  | approved/rejected/refunded/delivery event tokens |
| `title` | VARCHAR(220) |  | |
| `summary` | VARCHAR(220) |  | |
| `target_view` | VARCHAR(60) |  | currently `order-status` |
| `is_read` | BOOLEAN |  | |
| `created_at` | DATETIME |  | |
| `read_at` | DATETIME |  | |

#### `message_notifications` (from `message_notifications.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | VARCHAR(80) | PK | `notif-...` or legacy `msg-...` |
| `type` | VARCHAR(60) |  | `message`, `order` |
| `title` | VARCHAR(220) |  | |
| `summary` | VARCHAR(220) |  | |
| `payload` | JSON |  | Polymorphic payload |
| `is_read` | BOOLEAN |  | |
| `created_at` | DATETIME |  | |
| `read_at` | DATETIME |  | |

Payload structures observed:
- `type='order'`: `{ order_id, event? }`
- `type='message'`: `{ sender_name, sender_email, message, attachments[] }`

#### `message_notification_attachments` (normalized child from message payload)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `notification_id` | VARCHAR(80) | PK/FK | -> `message_notifications.id` |
| `position` | INT | PK | Array order |
| `path` | VARCHAR(500) |  | Stored attachment path |

### 3.5 Customer Payment Profile and App Settings Domain

#### `customer_gcash_profiles` (from `customer_gcash_profiles.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `customer_id` | VARCHAR(64) | PK/FK (external) | One row per customer |
| `gcash_name` | VARCHAR(120) |  | |
| `gcash_number` | VARCHAR(40) |  | |
| `updated_at` | DATETIME |  | |

#### `gcash_qr_settings` (from `gcash_qr.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | TINYINT | PK | Constant 1 (single-row table design) |
| `qrImagePath` | VARCHAR(500) |  | QR image path |
| `accountName` | VARCHAR(120) |  | |
| `accountNumber` | VARCHAR(40) |  | |
| `updatedAt` | DATETIME |  | |

#### `customer_terms` (from `customer_terms.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | TINYINT | PK | Constant 1 (single-row table design) |
| `contentHtml` | LONGTEXT |  | Sanitized TOC HTML |
| `updatedAt` | DATETIME |  | |

### 3.6 Archive Domain

#### `archived_products` (from `archives/products_archived.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | Archive id |
| `archivedAt` | DATETIME |  | |
| `originalKey` | VARCHAR(120) |  | Previous active `products.product_key` |
| `product` | JSON |  | Snapshot payload of product row |

#### `archived_equipment_units` (from `archives/equipment_units_archived.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | Unit archive id |
| `archivedAt` | DATETIME |  | |
| `productKey` | VARCHAR(120) | Weak FK | Product key at archive time |
| `model` | VARCHAR(160) |  | Model token |
| `reason` | VARCHAR(255) |  | Archive reason |
| `unit` | JSON |  | `{ serial, status }` |
| `productArchiveKey` | VARCHAR(180) | Optional FK-like | Links to `archived_products.archiveKey` when available |

#### `archived_how_it_works` (from `archives/how_it_works_archived.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | |
| `archivedAt` | DATETIME |  | |
| `slot` | INT |  | UI slot number |
| `imagePath` | VARCHAR(500) |  | Archived image path |

#### `archived_promo_banners` (from `archives/promo_banners_archived.json`)

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | |
| `archivedAt` | DATETIME |  | |
| `slot` | INT |  | Banner slot number |
| `imagePath` | VARCHAR(500) |  | Archived image path |

## 4) Status and Enum Tokens Found in Code

### Order status tokens
- `pending`
- `approved`
- `ongoing`
- `return`
- `completed`
- `canceled`
- `awaiting-refund`
- `rejected`
- `refunded`

### Delivery status tokens
- Receive leg: `not-required`, `waiting-proof`, `in-transit`, `closed`
- Return leg: `not-required`, `waiting-customer-proof`, `in-transit`, `closed`

### Method tokens
- Receiving/returning method: `pickup`, `meetup`, `delivery`
- Payment method: `gcash`, `cash-pickup`, `cash-meetup`
- Courier: `lalamove`, `grab-express`, `lbc`, `j-and-t`, `self-booked`
- Delivery actor / cancel actor: `admin`, `customer`, `system`

## 5) Data Generation / Mutation Paths (Who Writes What)

| Table(s) Affected | Main Writers (Endpoints / Pages) | Repository Function Layer |
| --- | --- | --- |
| `customer_orders`, `customer_order_items` | `customer_order_submit.php`, `customer_order_cancel.php`, `customer_order_upload_receipt.php`, `customer_order_upload_delivery_receipt.php`, `admin/dashboard/update_booking_status.php`, `admin/dashboard/upload_delivery_receipt.php`, `admin/dashboard/close_delivery_leg.php` | `append_customer_order_for_customer`, `cancel_customer_order_for_customer`, `upload_customer_order_receipt_for_customer`, `upload_customer_order_delivery_receipt_for_customer`, `upload_customer_order_delivery_receipt_for_admin`, `close_customer_order_delivery_leg_by_admin`, `update_customer_order_status_by_id`, `save_customer_orders_repository` |
| `customer_notifications` | Auto from order transitions + `customer_notifications_mark_read.php` | `append_customer_order_status_notification`, `append_customer_order_delivery_notification`, `save_customer_notifications_repository` |
| `message_notifications` | `customer_message_submit.php`, `customer_order_submit.php` (order placed), delivery hooks in order repo, admin notification mark-read/live-update endpoints | `append_message_notification`, `append_order_placed_notification`, `append_order_delivery_notification`, `save_message_notifications_repository` |
| `customer_gcash_profiles` | `account_settings_page_customer.php`, `customer_order_upload_receipt.php` | `upsert_customer_gcash_profile_for_customer` |
| `gcash_qr_settings` | `admin/dashboard/update_gcash_qr.php` | `save_gcash_qr_repository` |
| `customer_terms` | `admin/dashboard/update_customer_terms.php` | `save_customer_terms_repository` |
| `event_packages` | `events_page_customer.php` (admin mode), `admin/dashboard/archive_event_package.php`, `admin/dashboard/restore_archived_event_package.php` | `save_event_packages_repository` |
| `products`, `product_brands`, `product_categories` | `dashboard_page_admin.php`, `admin/dashboard/create_product.php`, `admin/dashboard/update_product.php`, `admin/dashboard/duplicate_product.php`, `admin/brands/index.php`, `admin/categories/index.php` | `save_products_repository`, `save_product_brands_repository`, `save_product_categories_repository` |
| `equipment_inventory_models`, `equipment_inventory_units`, `equipment_statuses` | `dashboard_page_admin.php`, `admin/brands/index.php`, order save/sync pipeline | `save_equipment_inventory_repository`, `save_equipment_statuses_repository`, inventory sync functions in order repo |
| Archive tables | `admin/dashboard/archive_product.php`, `admin/dashboard/restore_archived_product.php`, dashboard/admin archive actions for how-it-works and promo banners | `save_archived_products_repository`, `save_archived_equipment_units_repository`, `save_archived_how_it_works_repository`, `save_archived_promo_banners_repository` |

## 6) Practical Notes for SQL Migration

- JSON object maps (`products`, `event_packages`, `equipment_inventory`) should become normal tables with explicit key columns.
- Keep raw JSON columns (`specs`, `payload`, product snapshots) if you want minimal behavior change first.
- Normalize nested arrays (`items`, `units`, `recommendations`, attachments) into child tables when you need strict relational constraints.
- Keep current token values (status/method enums) as controlled lookup tables or SQL enums to preserve behavior.
- Keep order `status` label and optionally add a dedicated `status_token` column in SQL for cleaner transitions.
