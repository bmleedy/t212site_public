# Store Feature Manual Testing Guide

This document provides step-by-step manual testing procedures for the T-Shirt Store features.

## Prerequisites

1. Database tables created:
   - `orders`
   - `order_items`
   - `item_prices`
   - `store_config`

2. User accounts:
   - A user with `wm` or `sa` permission (for item price management)
   - A user with `trs`, `wm`, or `sa` permission (for order management)

3. PayPal sandbox credentials configured (for payment testing)

---

## Test Suite 1: Public Order Page (No Login Required)

### Test 1.1: Page Load
1. Navigate to `TShirtOrder.php` (logged out)
2. ✓ Page loads without errors
3. ✓ T-shirt image displays
4. ✓ Size selection dropdowns appear for all sizes (XS, S, M, L, XL, XXL)
5. ✓ Customer information form appears (Name, Email, Phone, Address)
6. ✓ PayPal buttons load

### Test 1.2: Price Display
1. View the order form
2. ✓ Each size shows its price
3. ✓ Prices match what's in `item_prices` table

### Test 1.3: Total Calculation
1. Select quantities for multiple sizes
2. ✓ Total updates automatically via JavaScript
3. ✓ Total is calculated correctly (qty × price for each size)
4. ✓ Changing quantities updates the total

### Test 1.4: Form Validation
1. Try to click PayPal button with empty form
2. ✓ Error message appears for missing required fields
3. Fill in only name, try again
4. ✓ Error for missing email/phone
5. Fill in all fields but select no items
6. ✓ Error that at least one item must be selected

### Test 1.5: PayPal Payment Flow (Sandbox)
1. Fill in valid customer information
2. Select at least one item
3. Click PayPal button
4. ✓ PayPal popup opens
5. Log in with sandbox buyer account
6. ✓ Amount shown matches total
7. Complete payment
8. ✓ Redirected to TShirtOrderComplete.php
9. ✓ Order confirmation displays

---

## Test Suite 2: Order Confirmation Page

### Test 2.1: Confirmation Display
1. Complete an order (or navigate to `TShirtOrderComplete.php?order_id=X`)
2. ✓ Order number displays
3. ✓ Customer name and email display
4. ✓ Items ordered display with quantities and prices
5. ✓ Total amount displays
6. ✓ Pickup instructions display

### Test 2.2: Invalid Order ID
1. Navigate to `TShirtOrderComplete.php?order_id=99999`
2. ✓ "Order Not Found" message displays
3. ✓ Link back to order page appears

---

## Test Suite 3: Order Management (Admin)

### Test 3.1: Access Control
1. Log out and try to access `ManageTShirtOrders.php`
2. ✓ Redirected to login or access denied
3. Log in as user WITHOUT trs/wm/sa permission
4. ✓ Access denied or no menu link visible
5. Log in as user WITH trs/wm/sa permission
6. ✓ Page loads successfully

### Test 3.2: Order List Display
1. Navigate to `ManageTShirtOrders.php`
2. ✓ Loading indicator appears briefly
3. ✓ Statistics panel shows: Total, Unfulfilled, Fulfilled, Revenue
4. ✓ Orders table loads with columns: Order #, Date, Customer, Email, Phone, Items, Total, Status, Actions
5. ✓ Default filter shows "Unfulfilled Only"

### Test 3.3: Filter Functionality
1. Change filter to "All Orders"
2. ✓ All orders display
3. Change filter to "Fulfilled Only"
4. ✓ Only fulfilled orders display
5. Click Refresh button
6. ✓ Orders reload

### Test 3.4: Order Details Modal
1. Click on an order number
2. ✓ Modal opens with order details
3. ✓ Shows customer info, items, and totals
4. ✓ Close button works

### Test 3.5: Mark Order Fulfilled
1. Find an unfulfilled order
2. Click "Mark Fulfilled" button
3. ✓ Confirmation dialog appears
4. Click OK
5. ✓ Order updates to "Fulfilled" status
6. ✓ Shows fulfilled by user name
7. ✓ Statistics update

### Test 3.6: CSV Export
1. Click "Export CSV" button
2. ✓ CSV file downloads
3. Open the CSV file
4. ✓ Contains order data with all fields
5. ✓ Items are properly formatted

---

## Test Suite 4: Item Price Management

### Test 4.1: Access Control
1. Log in as user WITHOUT wm/sa permission (e.g., treasurer only)
2. ✓ "Item Prices" menu link NOT visible
3. Try to access `ManageItemPrices.php` directly
4. ✓ Access denied
5. Log in as user WITH wm or sa permission
6. ✓ "Item Prices" menu link visible in Admin menu
7. ✓ Page loads successfully

### Test 4.2: Price List Display
1. Navigate to `ManageItemPrices.php`
2. ✓ All T-shirt sizes listed
3. ✓ Current prices displayed
4. ✓ Edit button/field for each item

### Test 4.3: Update Price
1. Find a T-shirt size item
2. Change the price (e.g., $15.00 to $16.00)
3. Save the change
4. ✓ Success message appears
5. ✓ Price updates in the list
6. Navigate to `TShirtOrder.php`
7. ✓ New price displays on public page

### Test 4.4: Activity Logging
1. Change a price
2. Navigate to `ActivityLog.php`
3. ✓ Price change logged with old and new values

---

## Test Suite 5: Email Notifications

### Test 5.1: Customer Confirmation Email
1. Place a test order (use your email)
2. ✓ Confirmation email received
3. ✓ Contains order number
4. ✓ Contains items ordered with quantities
5. ✓ Contains total amount
6. ✓ Contains pickup instructions

### Test 5.2: Treasurer Notification
1. Log in as treasurer
2. Navigate to User profile
3. ✓ Notification preferences section visible
4. Enable "T-shirt order notifications"
5. Place a test order
6. ✓ Treasurer receives notification email
7. ✓ Contains customer info and order details

### Test 5.3: Notification Opt-Out
1. Disable T-shirt order notifications for treasurer
2. Place a test order
3. ✓ Treasurer does NOT receive notification email
4. ✓ Customer still receives confirmation

---

## Test Suite 6: Activity Logging

### Test 6.1: Order Creation Logged
1. Place a new order
2. Navigate to `ActivityLog.php`
3. ✓ Entry for `store_order_created`
4. ✓ Contains order ID, customer email, total
5. ✓ Contains source IP address

### Test 6.2: Order Fulfillment Logged
1. Mark an order as fulfilled
2. Navigate to `ActivityLog.php`
3. ✓ Entry for `store_order_fulfilled`
4. ✓ Contains order ID
5. ✓ Shows user who fulfilled it

### Test 6.3: Price Change Logged
1. Change an item price
2. Navigate to `ActivityLog.php`
3. ✓ Entry for `item_price_updated`
4. ✓ Contains item code, old price, new price

---

## Test Suite 7: Edge Cases

### Test 7.1: Large Order
1. Order maximum quantity of each size
2. ✓ Total calculates correctly
3. ✓ Order processes successfully
4. ✓ All items appear in admin view

### Test 7.2: Special Characters in Fields
1. Enter name with apostrophe: "O'Connor"
2. Enter address with special characters
3. ✓ Order saves correctly
4. ✓ Data displays correctly (no XSS or broken HTML)

### Test 7.3: Concurrent Orders
1. Open two browser windows
2. Place orders simultaneously
3. ✓ Both orders save with unique IDs
4. ✓ No data corruption

### Test 7.4: Empty Order Type Filter
1. In ManageTShirtOrders, verify only tshirt orders show
2. ✓ Order type filter works correctly

---

## Cleanup

After testing:
1. Mark test orders as fulfilled (or delete from database)
2. Restore any changed prices to original values
3. Reset notification preferences

---

## Test Summary Template

| Test Suite | Pass | Fail | Notes |
|------------|------|------|-------|
| 1. Public Order Page |  |  |  |
| 2. Order Confirmation |  |  |  |
| 3. Order Management |  |  |  |
| 4. Item Prices |  |  |  |
| 5. Email Notifications |  |  |  |
| 6. Activity Logging |  |  |  |
| 7. Edge Cases |  |  |  |

**Tester:** _______________  **Date:** _______________

**Issues Found:**
_____________________________________________
_____________________________________________
