/**
 * SECTION 1: Public Pages (Logged Out)
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 1.
 * All tests run without authentication.
 */
const { test, expect } = require('@playwright/test');

// ---------------------------------------------------------------------------
// 1.1 Home Page & Navigation
// ---------------------------------------------------------------------------
test.describe('1.1 Home Page & Navigation', () => {
  test('home page loads without errors', async ({ page }) => {
    await page.goto('/index.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  test('Gig Harbor image displays correctly', async ({ page }) => {
    await page.goto('/index.php');
    const img = page.locator('img[src*="Gig-Harbor"]');
    await expect(img).toBeVisible();
  });

  test('chip links at bottom work: Calendar', async ({ page }) => {
    await page.goto('/index.php');
    const link = page.locator('a[href="Calendar.php"]');
    await expect(link).toBeVisible();
  });

  test('chip links at bottom work: Troop Photos', async ({ page }) => {
    await page.goto('/index.php');
    const link = page.locator('a[href*="facebook.com/Troop212"]');
    await expect(link).toBeVisible();
  });

  test('chip links at bottom work: Members', async ({ page }) => {
    await page.goto('/index.php');
    const link = page.locator('a[href="Members.php"]');
    await expect(link).toBeVisible();
  });

  test('chip links at bottom work: Recent Events', async ({ page }) => {
    await page.goto('/index.php');
    const link = page.locator('a[href="OutingsPublic.php"]');
    await expect(link).toBeVisible();
  });

  test('login form appears and is functional', async ({ page }) => {
    await page.goto('/login/index.php');
    await expect(page.locator('#user_name')).toBeVisible();
    await expect(page.locator('#user_password')).toBeVisible();
    await expect(page.locator('input[type="submit"]')).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// 1.2 Public Sidebar Navigation
// ---------------------------------------------------------------------------
test.describe('1.2 Public Sidebar Navigation', () => {
  test('Recent Events link works', async ({ page }) => {
    await page.goto('/index.php');
    await page.click('a[href="OutingsPublic.php"]');
    await expect(page).toHaveURL(/OutingsPublic\.php/);
  });

  test('Troop Calendar link works', async ({ page }) => {
    await page.goto('/index.php');
    await page.click('a[href="Calendar.php"]');
    await expect(page).toHaveURL(/Calendar\.php/);
  });

  test('Troop Photos link opens Facebook', async ({ page }) => {
    await page.goto('/index.php');
    const link = page.locator('a[href*="facebook.com/Troop212"]');
    await expect(link).toHaveAttribute('target', '_blank');
    await expect(link).toHaveAttribute('rel', /noopener/);
  });

  test('Members link works', async ({ page }) => {
    await page.goto('/index.php');
    await page.click('a[href="Members.php"]');
    await expect(page).toHaveURL(/Members\.php/);
  });

  test('Scoutmaster link works', async ({ page }) => {
    await page.goto('/index.php');
    const link = page.locator('a[href="Scoutmaster.php"]');
    await expect(link).toBeVisible();
  });
});

// ---------------------------------------------------------------------------
// 1.3 Donate Page
// ---------------------------------------------------------------------------
test.describe('1.3 Donate Page', () => {
  test('donate button visible in header bar with heart icon', async ({ page }) => {
    await page.goto('/index.php');
    const donateLink = page.locator('nav.top-bar a[href="Donate.php"]');
    await expect(donateLink).toBeVisible();
    await expect(donateLink).toHaveClass(/button/);
    await expect(donateLink).toHaveClass(/success/);
    const heartIcon = donateLink.locator('i.fi-heart');
    await expect(heartIcon).toBeVisible();
  });

  test('click donate button navigates to Donate.php', async ({ page }) => {
    await page.goto('/index.php');
    await page.click('nav.top-bar a[href="Donate.php"]');
    await expect(page).toHaveURL(/Donate\.php/);
  });

  test('preset amount buttons display ($25, $50, $100, Custom)', async ({ page }) => {
    await page.goto('/Donate.php');
    await expect(page.locator('.preset-amount[data-amount="25"]')).toBeVisible();
    await expect(page.locator('.preset-amount[data-amount="50"]')).toBeVisible();
    await expect(page.locator('.preset-amount[data-amount="100"]')).toBeVisible();
    await expect(page.locator('.preset-amount[data-amount="custom"]')).toBeVisible();
  });

  test('clicking a preset highlights it and shows PayPal buttons', async ({ page }) => {
    await page.goto('/Donate.php');
    const btn = page.locator('.preset-amount[data-amount="50"]');
    await btn.click();
    // Button should gain success class
    await expect(btn).toHaveClass(/success/);
    // Payment section should be visible
    await expect(page.locator('#paymentSection')).toBeVisible();
    // Donation total should show $50.00
    await expect(page.locator('#donationTotal')).toHaveText('50.00');
  });

  test('clicking Custom reveals amount input field', async ({ page }) => {
    await page.goto('/Donate.php');
    // Custom amount section should be hidden initially
    await expect(page.locator('#customAmountSection')).toBeHidden();
    // Click Custom
    await page.click('.preset-amount[data-amount="custom"]');
    // Custom amount section should now be visible
    await expect(page.locator('#customAmountSection')).toBeVisible();
    await expect(page.locator('#customAmount')).toBeVisible();
  });

  test('PayPal buttons load correctly', async ({ page }) => {
    await page.goto('/Donate.php');
    // The PayPal SDK script tag should be present
    const script = page.locator('script[src*="paypal.com/sdk/js"]');
    await expect(script).toHaveCount(1);
    // PayPal button container should exist
    await expect(page.locator('#paypal-button-container')).toBeAttached();
  });

  test('form validation prevents amounts below $1.00', async ({ page }) => {
    await page.goto('/Donate.php');
    // Click Custom and enter a low amount
    await page.click('.preset-amount[data-amount="custom"]');
    await page.fill('#customAmount', '0.50');
    // Payment section should remain hidden (amount too low)
    await expect(page.locator('#paymentSection')).toBeHidden();
  });
});

// ---------------------------------------------------------------------------
// 1.4 Public Content Pages
// ---------------------------------------------------------------------------
test.describe('1.4 Public Content Pages', () => {
  test('OutingsPublic.php displays events table', async ({ page }) => {
    await page.goto('/OutingsPublic.php');
    await expect(page.locator('table')).toBeVisible();
  });

  test('OutingsPublic.php shows "please log in" message', async ({ page }) => {
    await page.goto('/OutingsPublic.php');
    await expect(page.locator('text=please log in')).toBeVisible();
  });

  test('Calendar.php loads Google calendar iframe', async ({ page }) => {
    await page.goto('/Calendar.php');
    const iframe = page.locator('iframe[src*="google.com/calendar"]');
    await expect(iframe).toBeAttached();
  });

  test('CurrentInfo.php displays sections', async ({ page }) => {
    await page.goto('/CurrentInfo.php');
    await expect(page).toHaveTitle(/Troop 212/i);
    // Page should have content (committee table or meeting info)
    const content = page.locator('.large-9.columns');
    await expect(content).toBeVisible();
  });
});
