/**
 * SECTION 8: Permission-Based Access Testing
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 8.
 *
 * NOTE: Full permission-toggle testing (8.1-8.4) requires modifying
 * user permissions between tests, which is destructive. These tests
 * verify the current state rather than toggling permissions.
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 *   TEST_USER_USERNAME / TEST_USER_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA, loginAsTestUser } = require('./helpers/login');

test.describe('Section 8: Permission-Based Access', () => {
  // -------------------------------------------------------------------------
  // Verify SA user has full menu visibility
  // -------------------------------------------------------------------------
  test.describe('SA user has all menus', () => {
    test('SA user sees Leader Tools menu', async ({ page }) => {
      await loginAsSA(page);
      await page.goto('/ListEvents.php');
      await expect(page.locator('text=Leader Tools')).toBeVisible();
    });

    test('SA user sees Admin menu', async ({ page }) => {
      await loginAsSA(page);
      await page.goto('/ListEvents.php');
      await expect(page.locator('.admin-menu')).toBeVisible();
    });

    test('SA user sees Treasurer menu', async ({ page }) => {
      await loginAsSA(page);
      await page.goto('/ListEvents.php');
      await expect(page.locator('.treasurer-menu')).toBeVisible();
    });

    test('SA user sees Attendance Tracker link', async ({ page }) => {
      await loginAsSA(page);
      await page.goto('/ListEvents.php');
      await expect(page.locator('a[href="Attendance.php"]')).toBeVisible();
    });

    test('SA user sees Patrol Agenda link', async ({ page }) => {
      await loginAsSA(page);
      await page.goto('/ListEvents.php');
      await expect(page.locator('a[href="PatrolAgenda.php"]')).toBeVisible();
    });
  });

  // -------------------------------------------------------------------------
  // Verify test user menu visibility (depends on current permissions)
  // -------------------------------------------------------------------------
  test.describe('Test user menu visibility', () => {
    test('test user can log in and see sidebar', async ({ page }) => {
      await loginAsTestUser(page);
      await page.goto('/ListEvents.php');
      const sidebar = page.locator('.large-3.panel.columns');
      await expect(sidebar).toBeVisible();
    });
  });
});
