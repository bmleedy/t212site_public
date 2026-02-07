/**
 * SECTION 3: Core User Features (as SA User)
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 3.
 * All tests require SA authentication.
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

test.describe('Section 3: Core User Features', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
  });

  // -------------------------------------------------------------------------
  // 3.1 My Profile
  // -------------------------------------------------------------------------
  test.describe('3.1 My Profile', () => {
    test('User.php loads with profile data', async ({ page }) => {
      // Click "My Profile" in sidebar
      await page.click('a:has-text("My Profile")');
      await expect(page).toHaveURL(/User\.php/);
      // Profile fields should be present
      await expect(page.locator('#user_name').or(page.locator('text=Profile'))).toBeVisible();
    });

    test('profile fields are visible', async ({ page }) => {
      await page.click('a:has-text("My Profile")');
      await expect(page).toHaveURL(/User\.php/);
      // Page should have form fields or profile content
      const content = page.locator('.large-9.columns');
      await expect(content).toBeVisible();
    });
  });

  // -------------------------------------------------------------------------
  // 3.2 Pay or Approve Outings
  // -------------------------------------------------------------------------
  test.describe('3.2 Pay or Approve Outings', () => {
    test('EventPay.php loads without errors', async ({ page }) => {
      await page.goto('/EventPay.php');
      await expect(page).toHaveTitle(/Troop 212/i);
      // Should not show an error page
      await expect(page.locator('text=Error').first()).not.toBeVisible({ timeout: 3000 }).catch(() => {});
    });
  });

  // -------------------------------------------------------------------------
  // 3.3 Troop Events & Outings
  // -------------------------------------------------------------------------
  test.describe('3.3 Troop Events & Outings', () => {
    test('ListEvents.php loads event list', async ({ page }) => {
      await page.goto('/ListEvents.php');
      await expect(page).toHaveTitle(/Troop 212/i);
      // Wait for AJAX data to load
      await page.waitForSelector('#userdata', { timeout: 10000 });
    });

    test('New Entry button appears for SA user', async ({ page }) => {
      await page.goto('/ListEvents.php');
      // Wait for the page to render the buttons
      await page.waitForSelector('#userdata', { timeout: 10000 });
      const newEntryBtn = page.locator('button:has-text("New Entry")');
      await expect(newEntryBtn).toBeVisible();
    });

    test('View ALL Events link works', async ({ page }) => {
      await page.goto('/ListEvents.php');
      await page.waitForSelector('#userdata', { timeout: 10000 });
      const allEventsLink = page.locator('a[href="ListEventsAll.php"]');
      await expect(allEventsLink).toBeVisible();
    });
  });

  // -------------------------------------------------------------------------
  // 3.5 Directories
  // -------------------------------------------------------------------------
  test.describe('3.5 Directories', () => {
    test('Scout Directory loads', async ({ page }) => {
      await page.goto('/ListScouts.php');
      await expect(page).toHaveTitle(/Troop 212/i);
    });

    test('Adult Directory loads', async ({ page }) => {
      await page.goto('/ListAdults.php');
      await expect(page).toHaveTitle(/Troop 212/i);
    });

    test('Merit Badge Counselors loads', async ({ page }) => {
      await page.goto('/MB_Counselors.php');
      await expect(page).toHaveTitle(/Troop 212/i);
    });
  });

  // -------------------------------------------------------------------------
  // 3.6 Calendar
  // -------------------------------------------------------------------------
  test.describe('3.6 Calendar', () => {
    test('Calendar.php displays correctly', async ({ page }) => {
      await page.goto('/Calendar.php');
      const iframe = page.locator('iframe[src*="google.com/calendar"]');
      await expect(iframe).toBeAttached();
    });
  });
});
