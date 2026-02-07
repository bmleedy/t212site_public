/**
 * SECTION 8A: User Impersonation (SA Only)
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 8A.
 * Requires SA authentication.
 *
 * NOTE: These tests verify page access and UI elements. Actual
 * impersonation start/stop is tested carefully to avoid side effects.
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA, loginAsTestUser } = require('./helpers/login');

test.describe('Section 8A: User Impersonation', () => {
  // -------------------------------------------------------------------------
  // 8A.1 Access Control
  // -------------------------------------------------------------------------
  test.describe('8A.1 Access Control', () => {
    test('SA user can access Impersonate.php', async ({ page }) => {
      await loginAsSA(page);
      await page.goto('/Impersonate.php');
      await expect(page).toHaveTitle(/Troop 212/i);
      await expect(page.locator('text=Access Denied')).not.toBeVisible();
    });

    test('non-SA user gets Access Denied on Impersonate.php', async ({ page }) => {
      await loginAsTestUser(page);
      await page.goto('/Impersonate.php');
      // Should show Access Denied or redirect
      const denied = page.locator('text=Access Denied');
      const hasAccess = await denied.isVisible().catch(() => false);
      // Either Access Denied is shown OR the user was redirected away
      if (!hasAccess) {
        // If no explicit denial, the page should not show impersonation controls
        await expect(page.locator('text=Impersonate')).not.toBeVisible();
      }
    });
  });

  // -------------------------------------------------------------------------
  // 8A.2 Impersonate page has user list (SA)
  // -------------------------------------------------------------------------
  test('Impersonate page shows user list for SA', async ({ page }) => {
    await loginAsSA(page);
    await page.goto('/Impersonate.php');
    // Page should have some content (user list or search)
    const content = page.locator('.large-9.columns');
    await expect(content).toBeVisible();
  });
});
