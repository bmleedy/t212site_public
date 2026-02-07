/**
 * SECTION 6: Treasurer Features
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 6.
 * Requires SA authentication (has trs/sa permissions).
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

test.describe('Section 6: Treasurer Features', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
  });

  // -------------------------------------------------------------------------
  // 6.1 Payment Report
  // -------------------------------------------------------------------------
  test.describe('6.1 Payment Report', () => {
    test('TreasurerReport.php loads without errors', async ({ page }) => {
      await page.goto('/TreasurerReport.php');
      await expect(page).toHaveTitle(/Troop 212/i);
      // Should not show Access Denied
      await expect(page.locator('text=Access Denied')).not.toBeVisible();
    });
  });
});
