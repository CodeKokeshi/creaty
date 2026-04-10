# Delivery Flow Plan (Final Missing Flow)

## Goal
Build a complete Delivery flow that supports both directions:
- Receive leg: customer is receiver (customer should see delivery receipt uploaded by sender side).
- Return leg: customer is sender (customer should upload delivery receipt when sending back).

This plan is designed to fit the current architecture without breaking existing core booking statuses.

## Core Strategy (Low-Risk)
Keep existing main booking statuses:
- pending
- approved
- ongoing
- return
- completed

Add delivery leg state fields instead of adding many new global booking statuses.
This avoids large regressions in current status checks and UI gates.

## Delivery Leg Model
Use two parallel legs:
- receive_delivery_* : for delivery to customer
- return_delivery_* : for delivery back to shop

### Recommended Order Fields
Add these in order records (normalized in repository):
- receive_delivery_status
- receive_delivery_receipt_path
- receive_delivery_receipt_uploaded_at
- receive_delivery_receipt_uploaded_by
- receive_delivery_tracking_number
- receive_delivery_notes

- return_delivery_status
- return_delivery_receipt_path
- return_delivery_receipt_uploaded_at
- return_delivery_receipt_uploaded_by
- return_delivery_tracking_number
- return_delivery_notes

### Suggested Allowed Tokens
- receive_delivery_status:
  - not-required
  - awaiting-dispatch
  - in-transit
  - delivered

- return_delivery_status:
  - not-required
  - awaiting-customer-shipment
  - in-transit
  - received-back

## Direction Rules
### A) Receiving Method = delivery
- After booking approved:
  - receive_delivery_status = awaiting-dispatch
- Sender side uploads outbound delivery receipt:
  - receive_delivery_status = in-transit
  - customer can view this receipt in Order Status
- Admin confirms delivered (or handover confirmed):
  - receive_delivery_status = delivered
  - booking can continue with existing ongoing/return flow

### B) Returning Method = delivery
- When booking enters return:
  - return_delivery_status = awaiting-customer-shipment
  - customer sees Upload Delivery Receipt action
- Customer uploads return delivery receipt:
  - return_delivery_status = in-transit
  - admin sees receipt in booking detail
- Admin confirms package received:
  - return_delivery_status = received-back
  - then admin uses Complete (keep current rule)

## Upload Delivery Receipt Behavior (Your Wireframe Requirement)
Single UX concept, two contexts:
- Customer receiving (receive leg):
  - customer sees delivery receipt (read-only view).
  - uploaded by sender side (admin/courier workflow).
- Customer sending (return leg):
  - customer uploads delivery receipt.
  - this becomes proof of shipment for admin review.

## API / Endpoint Plan
### 1) Customer return upload endpoint
Create:
- customer_order_upload_delivery_receipt.php

Payload:
- orderId
- direction = return
- imageDataUrl
- optional trackingNumber
- optional notes

Validation:
- customer owns order
- returning_method = delivery
- statusToken = return
- return_delivery_status in [awaiting-customer-shipment, in-transit]

### 2) Admin receive upload endpoint
Create:
- admin/dashboard/upload_delivery_receipt.php

Payload:
- order_id
- direction = receive
- image_data_url
- optional tracking_number
- optional notes

Validation:
- receiving_method = delivery
- statusToken in [approved, ongoing]
- receive_delivery_status in [awaiting-dispatch, in-transit]

### 3) Repository helpers
Add functions in config/customer_orders_repository.php:
- normalize_customer_order_delivery_status_token($value, $direction)
- customer_order_requires_receive_delivery($record)
- customer_order_requires_return_delivery($record)
- save_customer_order_delivery_receipt_from_data_url(...)
- upload_customer_order_delivery_receipt_for_customer(...)
- upload_customer_order_delivery_receipt_for_admin(...)

## UI Plan
## Customer Order Status (js/script.js)
Add delivery-state rendering:
- If receive leg delivery:
  - awaiting-dispatch: "Preparing Delivery"
  - in-transit: "Delivery In Transit" + View Delivery Receipt
  - delivered: keep normal lifecycle labels

- If return leg delivery and status return:
  - awaiting-customer-shipment: show button "Upload Delivery Receipt"
  - in-transit: "Return Delivery In Transit" + View Uploaded Receipt
  - received-back: show "Return Package Received"

Modal pattern:
- Reuse existing native webpage modal pattern (same style as GCash upload modal).
- No browser-native alert/confirm/modal.

## Admin Booking Detail (dashboard_page_admin.php + js/script.js)
Add two media blocks under booking images page:
- Receive Delivery Receipt
- Return Delivery Receipt

Add admin actions (only when valid):
- Upload Delivery Receipt (receive leg)
- Confirm Delivered (receive leg)
- Confirm Return Package Received (return leg)

Keep existing:
- Complete remains final manual completion step.

## Notification Plan
Use existing customer notification repository pattern:
- On admin upload of receive delivery receipt:
  - notify customer that delivery is in transit with receipt available.
- On admin confirms delivered:
  - notify customer delivery received.
- On customer uploads return delivery receipt:
  - create admin-side signal (existing admin notification mechanism) for review.

## Storage and Assets
Create folders:
- assets/delivery_receipts/receive
- assets/delivery_receipts/return

Filename pattern:
- <order-id>-receive-delivery.<ext>
- <order-id>-return-delivery.<ext>

Allow overwrite policy:
- v1: overwrite latest file path for simplicity.
- Optional v2: keep history with timestamp suffixes.

## Backward Compatibility
For existing orders:
- normalize missing delivery fields to empty status and paths.
- default status:
  - receive_delivery_status = not-required unless receiving_method=delivery
  - return_delivery_status = not-required unless returning_method=delivery

## Recommended Implementation Phases
## Phase 1 (Data + backend contract)
- Add delivery fields and normalizers in repository.
- Add asset save helper for delivery receipts.
- Add customer return upload endpoint.
- Add admin receive upload endpoint.

## Phase 2 (Customer flow)
- Add Upload Delivery Receipt modal/action for return leg.
- Add view delivery receipt support in Order Status for both legs.
- Add customer-facing delivery labels.

## Phase 3 (Admin flow)
- Add delivery receipt panels in booking details.
- Add admin actions and button visibility rules.
- Add admin handling for delivered/received-back confirmations.

## Phase 4 (notifications + polish)
- Wire notifications for both legs.
- Add status-note copy for delivery branches.
- Ensure color/status readability in CSS.

## Acceptance Checklist
- Customer can upload return delivery receipt only when returning_method=delivery and status=return.
- Customer can view receive delivery receipt when receiving_method=delivery and sender side uploaded it.
- Admin can upload receive delivery receipt and review return delivery receipt.
- Delivery leg states are visible without breaking existing main booking statuses.
- Complete action still works as final close step.
- No browser-native modals are used.
- No unreadable UI states are introduced.
