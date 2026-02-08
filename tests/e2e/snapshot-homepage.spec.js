/**
 * Visual Snapshot Tests: Homepage
 *
 * Captures full-page and viewport screenshots and compares them against
 * baseline images stored in __screenshots__/.  Baselines are generated
 * in CI via the update-snapshots workflow.
 */
const { test, expect } = require('@playwright/test');

test.describe('Homepage visual snapshots', () => {
  test('full-page screenshot matches baseline', async ({ page }) => {
    await page.goto('/index.php');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveScreenshot('homepage-full.png', {
      fullPage: true,
    });
  });

  test('viewport screenshot matches baseline', async ({ page }) => {
    await page.goto('/index.php');
    await page.waitForLoadState('networkidle');

    await expect(page).toHaveScreenshot('homepage-viewport.png');
  });
});
