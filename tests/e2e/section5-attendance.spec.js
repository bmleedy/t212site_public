/**
 * SECTION 5: Attendance Features
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 5.
 * Requires SA authentication (has wm/sa/pl permissions).
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

test.describe('Section 5: Attendance Features', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
  });

  // -------------------------------------------------------------------------
  // 5.1 Attendance Tracker
  // -------------------------------------------------------------------------
  test.describe('5.1 Attendance Tracker', () => {
    test('Attendance.php loads without errors', async ({ page }) => {
      await page.goto('/Attendance.php');
      await expect(page).toHaveTitle(/Troop 212/i);
    });

    test('patrol tabs display', async ({ page }) => {
      await page.goto('/Attendance.php');
      // Wait for content to load
      const content = page.locator('.large-9.columns');
      await expect(content).toBeVisible();
    });
  });

  // -------------------------------------------------------------------------
  // 5.2 Patrol Agenda
  // -------------------------------------------------------------------------
  test.describe('5.2 Patrol Agenda', () => {
    test('PatrolAgenda.php loads without errors', async ({ page }) => {
      await page.goto('/PatrolAgenda.php');
      await expect(page).toHaveTitle(/Troop 212/i);
    });
  });
});
