/**
 * SECTION 7: Admin Menu
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 7.
 * Requires SA authentication.
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

test.describe('Section 7: Admin Menu', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
    await page.goto('/index.php');
  });

  // -------------------------------------------------------------------------
  // 7.1 Menu Functionality
  // -------------------------------------------------------------------------
  test.describe('7.1 Menu Functionality', () => {
    test('Admin expandable menu appears in sidebar', async ({ page }) => {
      await expect(page.locator('.admin-menu')).toBeVisible();
      await expect(page.locator('text=Admin')).toBeVisible();
    });

    test('click expands and collapses the submenu', async ({ page }) => {
      const adminMenu = page.locator('#adminMenuItems');
      // Initially hidden
      await expect(adminMenu).toBeHidden();
      // Click to expand
      await page.locator('.admin-menu a:has-text("Admin")').click();
      await expect(adminMenu).toBeVisible();
      // Click to collapse
      await page.locator('.admin-menu a:has-text("Admin")').click();
      await expect(adminMenu).toBeHidden();
    });

    test('arrow icon toggles direction', async ({ page }) => {
      const arrow = page.locator('#adminMenuArrow');
      await expect(arrow).toHaveClass(/fi-arrow-down/);
      await page.locator('.admin-menu a:has-text("Admin")').click();
      await expect(arrow).toHaveClass(/fi-arrow-up/);
    });
  });

  // -------------------------------------------------------------------------
  // 7.2 Manage Patrols
  // -------------------------------------------------------------------------
  test('Patrols.php loads', async ({ page }) => {
    await page.goto('/Patrols.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  // -------------------------------------------------------------------------
  // 7.3 Activity Log
  // -------------------------------------------------------------------------
  test('ActivityLog.php loads without errors', async ({ page }) => {
    await page.goto('/ActivityLog.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  // -------------------------------------------------------------------------
  // 7.4 Permissions
  // -------------------------------------------------------------------------
  test('Permissions.php loads with users list', async ({ page }) => {
    await page.goto('/Permissions.php');
    await expect(page).toHaveTitle(/Troop 212/i);
    await expect(page.locator('text=Access Denied')).not.toBeVisible();
  });
});
