/**
 * SECTION 4: Leader Tools Menu
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 4.
 * Requires SA authentication (has all permissions).
 *
 * Requires environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

test.describe('Section 4: Leader Tools Menu', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsSA(page);
    await page.goto('/ListEvents.php');
  });

  // -------------------------------------------------------------------------
  // 4.1 Menu Functionality
  // -------------------------------------------------------------------------
  test.describe('4.1 Menu Functionality', () => {
    test('Leader Tools expandable menu appears in sidebar', async ({ page }) => {
      await expect(page.locator('text=Leader Tools')).toBeVisible();
    });

    test('click expands and collapses the submenu', async ({ page }) => {
      const leaderMenu = page.locator('#leaderMenuItems');
      // Initially hidden
      await expect(leaderMenu).toBeHidden();
      // Click to expand
      await page.click('text=Leader Tools');
      await expect(leaderMenu).toBeVisible();
      // Click to collapse
      await page.click('text=Leader Tools');
      await expect(leaderMenu).toBeHidden();
    });

    test('arrow icon toggles direction', async ({ page }) => {
      const arrow = page.locator('#leaderMenuArrow');
      // Initially down
      await expect(arrow).toHaveClass(/fi-arrow-down/);
      // Click to expand
      await page.click('text=Leader Tools');
      await expect(arrow).toHaveClass(/fi-arrow-up/);
    });
  });

  // -------------------------------------------------------------------------
  // 4.2 New User
  // -------------------------------------------------------------------------
  test('registernew.php loads without errors', async ({ page }) => {
    await page.goto('/registernew.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  // -------------------------------------------------------------------------
  // 4.3 Deleted Users
  // -------------------------------------------------------------------------
  test('ListDeletes.php loads', async ({ page }) => {
    await page.goto('/ListDeletes.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  // -------------------------------------------------------------------------
  // 4.4 Event Signups
  // -------------------------------------------------------------------------
  test('EventSignups.php loads', async ({ page }) => {
    await page.goto('/EventSignups.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  // -------------------------------------------------------------------------
  // 4.5 Attendance Report
  // -------------------------------------------------------------------------
  test('AttendanceReport.php loads without errors', async ({ page }) => {
    await page.goto('/AttendanceReport.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });

  // -------------------------------------------------------------------------
  // 4.6 Manage Committee
  // -------------------------------------------------------------------------
  test('ManageCommittee.php loads with committee roles table', async ({ page }) => {
    await page.goto('/ManageCommittee.php');
    await expect(page).toHaveTitle(/Troop 212/i);
  });
});
