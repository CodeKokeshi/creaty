# CREATY SQL Visual Tables

## customer_accounts

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | INT AUTO_INCREMENT | PK | |
| `first_name` | VARCHAR(100) |  | |
| `last_name` | VARCHAR(100) |  | |
| `email` | VARCHAR(190) | UNIQUE | |
| `skill_level` | VARCHAR(32) |  | |
| `password` | VARCHAR(255) |  | |
| `email_verified_at` | TIMESTAMP NULL |  | |
| `privacy_policy_accepted_at` | TIMESTAMP NULL |  | |
| `created_at` | TIMESTAMP |  | |

| id | first_name | last_name | email | skill_level | password | email_verified_at | privacy_policy_accepted_at | created_at |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| 4 | qwe | qwe | qwe@gmail.com | Beginner | (hashed) |  |  |  |

## admin_accounts

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | INT AUTO_INCREMENT | PK | |
| `username` | VARCHAR(50) | UNIQUE | |
| `employee_number` | VARCHAR(50) | UNIQUE | Optional nullable unique |
| `password` | VARCHAR(255) |  | |
| `created_at` | TIMESTAMP |  | |

| id | username | employee_number | password | created_at |
| --- | --- | --- | --- | --- |
| 1 | admin | 132245 | $2y$10$QxEyUfqp9N... | 2026-03-12 07:57:09 |

## staff_accounts

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | INT AUTO_INCREMENT | PK | |
| `name` | VARCHAR(190) |  | |
| `email` | VARCHAR(190) | UNIQUE | |
| `password` | VARCHAR(255) |  | |
| `created_at` | TIMESTAMP |  | |

| id | name | email | password | created_at |
| --- | --- | --- | --- | --- |
|  |  |  |  |  |

## product_brands

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `brand_name` | VARCHAR(120) | PK | |

| brand_name |
| --- |
| Canon |

## product_categories

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `category_name` | VARCHAR(120) | PK | |

| category_name |
| --- |
| Photography |

## products

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK | |
| `brand` | VARCHAR(120) | FK-like | |
| `name` | VARCHAR(160) |  | |
| `skillLevel` | VARCHAR(60) |  | |
| `skillLevels` | JSON |  | |
| `category` | VARCHAR(120) | FK-like | |
| `price` | DECIMAL(10,2) |  | |
| `discountPercent` | INT |  | |
| `spec1` | VARCHAR(255) |  | |
| `spec2` | VARCHAR(255) |  | |
| `tagline` | VARCHAR(255) |  | |
| `cameraImage` | VARCHAR(500) |  | |
| `captureSlides` | JSON |  | |
| `specs` | JSON |  | |
| `recommendations` | JSON |  | |
| `informationImages` | JSON |  | |

| product_key | brand | name | skillLevel | skillLevels | category | price | discountPercent | spec1 | spec2 | tagline | cameraImage | captureSlides | specs | recommendations | informationImages |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| canon-700d | Canon | 700D | Beginner |  | Photography | 800.00 | 50 | 18MP APS-C CMOS sensor | 1080p Full HD video recording at up to 30 fps | 18MP APS-C CMOS sensor and 1080p Full HD recording. | assets/cameras/Canon%20700D.png | ["Street portrait placeholder","Indoor sample placeholder","Outdoor detail placeholder"] | {"Brand":["Canon"],"Imaging and Performance":["Sensor: 18MP APS-C CMOS sensor"]} | ["nikon-d60","sony-zv-e10","fuji-x-a3"] | ["assets/cameras/product_information/Canon%20700D%20Information.jpg"] |

## product_recommendations

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK/FK | Source product |
| `recommended_product_key` | VARCHAR(120) | PK/FK | Target product |
| `position` | INT | PK | Array index order |

| product_key | recommended_product_key | position |
| --- | --- | --- |
| canon-700d | nikon-d60 | 0 |

## event_packages

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `package_key` | VARCHAR(120) | PK | |
| `title` | VARCHAR(180) |  | |
| `price` | DECIMAL(10,2) |  | |
| `discountPercent` | INT |  | |
| `folder` | VARCHAR(40) |  | |
| `thumbnail_images` | JSON |  | |
| `archived` | BOOLEAN |  | |
| `archivedAt` | DATETIME |  | |

| package_key | title | price | discountPercent | folder | thumbnail_images | archived | archivedAt |
| --- | --- | --- | --- | --- | --- | --- | --- |
| wedding | WEDDING PACKAGE | 800.00 | 10 | 0000 | ["assets/event_packages/0000/civil-wedding_Jerome-and-Marian/CLT05321.jpg"] | false |  |

## event_package_thumbnail_images

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `package_key` | VARCHAR(120) | PK/FK | |
| `position` | INT | PK | |
| `image_path` | VARCHAR(500) |  | |

| package_key | position | image_path |
| --- | --- | --- |
| wedding | 0 | assets/event_packages/0000/civil-wedding_Jerome-and-Marian/CLT05321.jpg |

## equipment_statuses

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `status_token` | VARCHAR(50) | PK | |

| status_token |
| --- |
| available |

## equipment_inventory_models

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK/FK | |
| `timesUsed` | INT |  | |
| `nextSerial` | INT |  | |

| product_key | timesUsed | nextSerial |
| --- | --- | --- |
| canon-700d | 12 | 1 |

## equipment_inventory_units

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `product_key` | VARCHAR(120) | PK/FK | |
| `serial` | INT | PK | |
| `status` | VARCHAR(50) | FK-like | |

| product_key | serial | status |
| --- | --- | --- |
| canon-700d | 0 | available |

## customer_orders

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | VARCHAR(64) | PK | |
| `customer_id` | VARCHAR(64) | FK external | |
| `customer_name` | VARCHAR(180) |  | |
| `customer_email` | VARCHAR(180) |  | |
| `status` | VARCHAR(40) |  | |
| `receive_date` | DATE |  | |
| `receive_time` | TIME |  | |
| `return_date` | DATE |  | |
| `return_time` | TIME |  | |
| `place` | VARCHAR(255) |  | |
| `receiving_method` | ENUM-like |  | |
| `returning_method` | ENUM-like |  | |
| `courier` | ENUM-like |  | |
| `valid_id_path` | VARCHAR(500) |  | |
| `valid_id_uploaded_at` | DATETIME |  | |
| `selfie_with_id_path` | VARCHAR(500) |  | |
| `selfie_with_id_uploaded_at` | DATETIME |  | |
| `cancel_reason` | VARCHAR(500) |  | |
| `canceled_by` | ENUM-like |  | |
| `payment_method` | ENUM-like |  | |
| `payment_receipt_path` | VARCHAR(500) |  | |
| `payment_receipt_uploaded_at` | DATETIME |  | |
| `receive_delivery_status` | ENUM-like |  | |
| `receive_delivery_receipt_path` | VARCHAR(500) |  | |
| `receive_delivery_receipt_uploaded_at` | DATETIME |  | |
| `receive_delivery_receipt_uploaded_by` | ENUM-like |  | |
| `receive_delivery_reference` | VARCHAR(120) |  | |
| `receive_delivery_notes` | VARCHAR(500) |  | |
| `receive_delivery_closed_at` | DATETIME |  | |
| `receive_delivery_closed_by` | ENUM-like |  | |
| `return_delivery_status` | ENUM-like |  | |
| `return_delivery_receipt_path` | VARCHAR(500) |  | |
| `return_delivery_receipt_uploaded_at` | DATETIME |  | |
| `return_delivery_receipt_uploaded_by` | ENUM-like |  | |
| `return_delivery_reference` | VARCHAR(120) |  | |
| `return_delivery_notes` | VARCHAR(500) |  | |
| `return_delivery_closed_at` | DATETIME |  | |
| `return_delivery_closed_by` | ENUM-like |  | |
| `receive_handover_confirmed_at` | DATETIME |  | |
| `receive_handover_confirmed_by` | ENUM-like |  | |
| `refund_proof_path` | VARCHAR(500) |  | |
| `refund_proof_uploaded_at` | DATETIME |  | |
| `created_at` | DATETIME |  | |

| id | customer_id | customer_name | customer_email | status | receive_date | receive_time | return_date | return_time | place | receiving_method | returning_method | courier | payment_method | receive_delivery_status | return_delivery_status | created_at |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ord-20260413231540-d1d3ad10 | 4 | qwe qwe | qwe@gmail.com | Pending | 2026-04-15 | 09:00 | 2026-04-16 | 09:00 |  | pickup | pickup |  | cash-pickup | not-required | not-required | 2026-04-13T23:15:40+08:00 |

## customer_order_items

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `order_id` | VARCHAR(64) | PK/FK | |
| `item_index` | INT | PK | |
| `name` | VARCHAR(200) |  | |
| `qty` | INT |  | |
| `days` | INT |  | |
| `item_id` | VARCHAR(160) |  | |
| `item_type` | VARCHAR(60) |  | |
| `product_key` | VARCHAR(120) | FK-like | |
| `event_package_key` | VARCHAR(120) | Derived FK-like | |

| order_id | item_index | name | qty | days | item_id | item_type | product_key | event_package_key |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| ord-20260413231540-d1d3ad10 | 0 | Canon 700D | 1 | 1 | camera-canon-700d | camera | canon-700d |  |

## customer_order_item_assigned_units

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `order_id` | VARCHAR(64) | PK/FK | |
| `item_index` | INT | PK/FK | |
| `product_key` | VARCHAR(120) | FK-like | |
| `serial` | INT | PK | |
| `unit_id` | VARCHAR(120) |  | |

| order_id | item_index | product_key | serial | unit_id |
| --- | --- | --- | --- | --- |
|  |  |  |  |  |

## customer_notifications

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | VARCHAR(80) | PK | |
| `customer_id` | VARCHAR(64) | FK external | |
| `type` | VARCHAR(60) |  | |
| `order_id` | VARCHAR(64) | FK-like | |
| `status_token` | VARCHAR(80) |  | |
| `title` | VARCHAR(220) |  | |
| `summary` | VARCHAR(220) |  | |
| `target_view` | VARCHAR(60) |  | |
| `is_read` | BOOLEAN |  | |
| `created_at` | DATETIME |  | |
| `read_at` | DATETIME |  | |

| id | customer_id | type | order_id | status_token | title | summary | target_view | is_read | created_at | read_at |
| --- | --- | --- | --- | --- | --- | --- | --- | --- | --- | --- |
|  |  |  |  |  |  |  |  |  |  |  |

## message_notifications

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | VARCHAR(80) | PK | |
| `type` | VARCHAR(60) |  | |
| `title` | VARCHAR(220) |  | |
| `summary` | VARCHAR(220) |  | |
| `payload` | JSON |  | |
| `is_read` | BOOLEAN |  | |
| `created_at` | DATETIME |  | |
| `read_at` | DATETIME |  | |

| id | type | title | summary | payload | is_read | created_at | read_at |
| --- | --- | --- | --- | --- | --- | --- | --- |
| notif-20260413151540-a5db387d | order | A new order has been placed: ORD-20260413231540-D1D3AD10 | A new order has been placed: ORD-20260413231540-D1D3AD10 | {"order_id":"ORD-20260413231540-D1D3AD10"} | false | 2026-04-13T15:15:40+00:00 |  |

## message_notification_attachments

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `notification_id` | VARCHAR(80) | PK/FK | |
| `position` | INT | PK | |
| `path` | VARCHAR(500) |  | |

| notification_id | position | path |
| --- | --- | --- |
| notif-20260402055426-0818b51f | 0 | assets/message_attachments/20260402055426-7f014a426b83-0002.png |

## customer_gcash_profiles

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `customer_id` | VARCHAR(64) | PK/FK external | |
| `gcash_name` | VARCHAR(120) |  | |
| `gcash_number` | VARCHAR(40) |  | |
| `updated_at` | DATETIME |  | |

| customer_id | gcash_name | gcash_number | updated_at |
| --- | --- | --- | --- |
| 4 | Mark Ardie Dolar | 09380432591 | 2026-04-13T12:57:06+00:00 |

## gcash_qr_settings

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | TINYINT | PK | Constant 1 |
| `qrImagePath` | VARCHAR(500) |  | |
| `accountName` | VARCHAR(120) |  | |
| `accountNumber` | VARCHAR(40) |  | |
| `updatedAt` | DATETIME |  | |

| id | qrImagePath | accountName | accountNumber | updatedAt |
| --- | --- | --- | --- | --- |
| 1 | assets/gcash_qr/gcash-qr.png | Admin Gcash | 09123456789 | 2026-04-08T09:46:19+00:00 |

## customer_terms

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `id` | TINYINT | PK | Constant 1 |
| `contentHtml` | LONGTEXT |  | |
| `updatedAt` | DATETIME |  | |

| id | contentHtml | updatedAt |
| --- | --- | --- |
| 1 | <h3>Key Rental Rules</h3> ... | 2026-04-09T00:21:22+00:00 |

## archived_products

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | Archive id |
| `archivedAt` | DATETIME |  | |
| `originalKey` | VARCHAR(120) |  | Previous active `products.product_key` |
| `product` | JSON |  | Snapshot payload of product row |

| archiveKey | archivedAt | originalKey | product |
| --- | --- | --- | --- |
| Nikon Ardie 20260324-150311 | 2026-03-24T15:03:11+00:00 | canon-700d-copy | {"brand":"Nikon","name":"Ardie","price":"800.00"} |

## archived_equipment_units

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | Unit archive id |
| `archivedAt` | DATETIME |  | |
| `productKey` | VARCHAR(120) | Weak FK | |
| `model` | VARCHAR(160) |  | |
| `reason` | VARCHAR(255) |  | |
| `unit` | JSON |  | `{ serial, status }` |
| `productArchiveKey` | VARCHAR(180) | Optional FK-like | |

| archiveKey | archivedAt | productKey | model | reason | unit | productArchiveKey |
| --- | --- | --- | --- | --- | --- | --- |
| CANON_NEWPRODUCTCOPY_000_20260327-093924 | 2026-03-27T09:39:24+00:00 | canon-new-product-copy | CANON_NEWPRODUCTCOPY | Removed from active inventory | {"serial":0,"status":"available"} |  |

## archived_how_it_works

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | |
| `archivedAt` | DATETIME |  | |
| `slot` | INT |  | UI slot number |
| `imagePath` | VARCHAR(500) |  | Archived image path |

| archiveKey | archivedAt | slot | imagePath |
| --- | --- | --- | --- |
| how-it-works-4-20260326-102556 | 2026-03-26T10:25:56+00:00 | 4 | assets/how_it_works/_archived/how-it-works-4-20260326-102556.png |

## archived_promo_banners

| Column | SQL Type | Key | Notes |
| --- | --- | --- | --- |
| `archiveKey` | VARCHAR(180) | PK | |
| `archivedAt` | DATETIME |  | |
| `slot` | INT |  | Banner slot number |
| `imagePath` | VARCHAR(500) |  | Archived image path |

| archiveKey | archivedAt | slot | imagePath |
| --- | --- | --- | --- |
| promo-banner-4-20260326-102653 | 2026-03-26T10:26:53+00:00 | 4 | assets/promo_images/_archived/promo-banner-4-20260326-102653.png |
