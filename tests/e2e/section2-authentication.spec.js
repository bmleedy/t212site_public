/**
 * SECTION 2: Authentication
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 2.
 * Tests login/logout flow.
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 *   TEST_USER_USERNAME / TEST_USER_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA, loginAsTestUser, logout } = require('./helpers/login');

test.describe('2.1 Login/Logout', () => {
  test('login with SA account succeeds', async ({ page }) => {
    await loginAsSA(page);
    // Should see Welcome greeting in the nav bar
    await expect(page.locator('text=Welcome').first()).toBeVisible();
  });

  test('sidebar changes to logged-in menu after login', async ({ page }) => {
    await loginAsSA(page);
    // Authenticated sidebar should have "My Profile" link
    const sidebar = page.locator('.large-3.panel.columns');
    await expect(sidebar.locator('text=My Profile')).toBeVisible();
  });

  test('logoff link works and returns to public view', async ({ page }) => {
    await loginAsSA(page);
    await logout(page);
    // After logout, login form should be available
    await page.goto('/login/index.php');
    await expect(page.locator('#user_name')).toBeVisible();
  });

  test('login with test user account succeeds', async ({ page }) => {
    await loginAsTestUser(page);
    await expect(page.locator('text=Welcome').first()).toBeVisible();
  });
});
