/**
 * SECTION 11: Mobile Responsiveness
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 11.
 * Uses a mobile viewport to verify responsive behavior.
 */
const { test, expect } = require('@playwright/test');

const mobileViewport = { width: 375, height: 812 }; // iPhone X dimensions

test.describe('Section 11: Mobile Responsiveness', () => {
  test('home page displays correctly on mobile', async ({ page }) => {
    await page.setViewportSize(mobileViewport);
    await page.goto('/index.php');
    await expect(page).toHaveTitle(/Troop 212/i);
    // Page should still render content
    const body = page.locator('body');
    await expect(body).toBeVisible();
  });

  test('top-bar hamburger menu appears on mobile', async ({ page }) => {
    await page.setViewportSize(mobileViewport);
    await page.goto('/index.php');
    // The toggle-topbar menu icon should be visible on mobile
    const menuToggle = page.locator('.toggle-topbar.menu-icon');
    await expect(menuToggle).toBeVisible();
  });

  test('large-screen sidebar is hidden on mobile', async ({ page }) => {
    await page.setViewportSize(mobileViewport);
    await page.goto('/index.php');
    // The visible-for-large-up wrapper should hide its content on mobile
    const largeOnly = page.locator('.visible-for-large-up');
    // Elements with this class should not be visible at mobile widths
    const count = await largeOnly.count();
    for (let i = 0; i < count; i++) {
      await expect(largeOnly.nth(i)).toBeHidden();
    }
  });

  test('Donate.php is usable on mobile', async ({ page }) => {
    await page.setViewportSize(mobileViewport);
    await page.goto('/Donate.php');
    await expect(page).toHaveTitle(/Troop 212/i);
    // Preset amount buttons should still be visible
    await expect(page.locator('.preset-amount[data-amount="25"]')).toBeVisible();
    await expect(page.locator('.preset-amount[data-amount="50"]')).toBeVisible();
  });

  test('OutingsPublic.php table is readable on mobile', async ({ page }) => {
    await page.setViewportSize(mobileViewport);
    await page.goto('/OutingsPublic.php');
    await expect(page.locator('table')).toBeVisible();
  });
});
