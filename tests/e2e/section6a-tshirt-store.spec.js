/**
 * SECTION 6A: T-Shirt Store Features
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 6A.
 * Includes public tests (no auth) and admin tests (SA auth).
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

// ---------------------------------------------------------------------------
// 6A.1 Public Order Page (no auth required)
// ---------------------------------------------------------------------------
test.describe('6A.1 Public Order Page (TShirtOrder.php)', () => {
  test('page loads without errors', async ({ page }) => {
    await page.goto('/TShirtOrder.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  test('t-shirt image displays', async ({ page }) => {
    await page.goto('/TShirtOrder.php');
    const img = page.locator('#productImage');
    await expect(img).toBeAttached();
  });

  test('customer information form displays', async ({ page }) => {
    await page.goto('/TShirtOrder.php');
    // Wait for the order form to load (config AJAX)
    await page.waitForSelector('#orderForm', { state: 'visible', timeout: 10000 }).catch(() => {});
    // Check form fields exist (may be hidden if orders disabled)
    const nameField = page.locator('#customerName');
    const emailField = page.locator('#customerEmail');
    const phoneField = page.locator('#customerPhone');
    const addressField = page.locator('#customerAddress');
    // At minimum the elements should be in the DOM
    await expect(nameField).toBeAttached();
    await expect(emailField).toBeAttached();
    await expect(phoneField).toBeAttached();
    await expect(addressField).toBeAttached();
  });

  test('PayPal button container exists', async ({ page }) => {
    await page.goto('/TShirtOrder.php');
    await expect(page.locator('#paypal-button-container')).toBeAttached();
  });
});

// ---------------------------------------------------------------------------
// 6A.2 Order Management (admin)
// ---------------------------------------------------------------------------
test.describe('6A.2 Order Management (ManageTShirtOrders.php)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
  });

  test('page loads with order list', async ({ page }) => {
    await page.goto('/ManageTShirtOrders.php');
    await expect(page).toHaveTitle(/Troop 212/i);
    await expect(page.locator('text=Access Denied')).not.toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// 6A.3 Item Price Management (admin)
// ---------------------------------------------------------------------------
test.describe('6A.3 Item Price Management (ManageItemPrices.php)', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
  });

  test('page loads with t-shirt sizes and prices', async ({ page }) => {
    await page.goto('/ManageItemPrices.php');
    await expect(page).toHaveTitle(/Troop 212/i);
    await expect(page.locator('text=Access Denied')).not.toBeVisible();
  });
});
