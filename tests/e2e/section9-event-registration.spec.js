/**
 * SECTION 9: Event Registration Flow (Non-Destructive)
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 9.
 * View-only tests — does NOT click registration buttons.
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

test.describe('Section 9: Event Registration Flow', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
  });

  test('can navigate to an existing event', async ({ page }) => {
    await page.goto('/ListEvents.php');
    // Wait for events to load via AJAX
    await page.waitForSelector('#userdata', { timeout: 10000 });

    // Try to find any event link
    const eventLink = page.locator('#userdata a[href*="Event.php"]').first();
    const hasEvents = await eventLink.isVisible().catch(() => false);

    if (hasEvents) {
      await eventLink.click();
      await expect(page).toHaveURL(/Event\.php/);
      // Event page should have event details
      await expect(page.locator('.large-9.columns')).toBeVisible();
    }
    // If no events exist, the test passes (nothing to navigate to)
  });
});
