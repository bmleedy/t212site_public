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
    // Foundation uses clip technique (1x1px, position:absolute) instead of
    // display:none for visible-for-large-up at small viewports.
    // Check that these elements are effectively invisible by verifying
    // their bounding box is tiny (clipped to 1x1px).
    const largeOnly = page.locator('.visible-for-large-up');
    const count = await largeOnly.count();
    for (let i = 0; i < count; i++) {
      const box = await largeOnly.nth(i).boundingBox();
      // Element should be clipped to 1x1px or have no bounding box
      if (box) {
        expect(box.width <= 1 && box.height <= 1).toBeTruthy();
      }
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
