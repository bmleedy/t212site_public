/**
 * SECTION 10: Browser Compatibility Check
 *
 * Tests from RELEASE_TESTING_CHECKLIST.md Section 10.
 * Checks for JavaScript console errors on key pages.
 */
const { test, expect } = require('@playwright/test');
const { loginAsSA } = require('./helpers/login');

/**
 * Collect JS console errors on a page.
 * Returns array of error message strings.
 */
function collectConsoleErrors(page) {
  const errors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') {
      const text = msg.text();
      // Ignore known benign errors (third-party scripts, CORS, etc.)
      if (text.includes('favicon.ico') ||
          text.includes('the server responded with a status of') ||
          text.includes('paypal') ||
          text.includes('PayPal') ||
          text.includes('seal.godaddy.com') ||
          text.includes('ERR_BLOCKED_BY_CLIENT') ||
          text.includes('net::')) {
        return;
      }
      errors.push(text);
    }
  });
  return errors;
}

test.describe('Section 10: Browser Compatibility (Console Errors)', () => {
  test('no JS errors on home page (public)', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/index.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    expect(errors).toEqual([]);
  });

  test('no JS errors on Calendar.php (public)', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/Calendar.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    expect(errors).toEqual([]);
  });

  test('no JS errors on OutingsPublic.php (public)', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/OutingsPublic.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    expect(errors).toEqual([]);
  });

  test('no JS errors on Donate.php (public)', async ({ page }) => {
    const errors = collectConsoleErrors(page);
    await page.goto('/Donate.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    expect(errors).toEqual([]);
  });

  test('no JS errors on ListEvents.php (authenticated)', async ({ page }) => {
    await loginAsSA(page);
    const errors = collectConsoleErrors(page);
    await page.goto('/ListEvents.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    expect(errors).toEqual([]);
  });

  test('no JS errors on index.php (authenticated)', async ({ page }) => {
    await loginAsSA(page);
    const errors = collectConsoleErrors(page);
    await page.goto('/index.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    expect(errors).toEqual([]);
  });

  test('no JS errors on sidebar menu toggle', async ({ page }) => {
    await loginAsSA(page);
    const errors = collectConsoleErrors(page);
    // Navigate to a page that includes the authenticated sidebar (m_sidebar.html)
    await page.goto('/ListEvents.php');
    await page.waitForLoadState('load');
    await page.waitForTimeout(1000);
    // Toggle Leader Tools menu
    const leaderTools = page.locator('text=Leader Tools');
    if (await leaderTools.isVisible()) {
      await leaderTools.click();
      await leaderTools.click();
    }
    // Toggle Admin menu
    const adminLink = page.locator('.admin-menu a:has-text("Admin")');
    if (await adminLink.isVisible()) {
      await adminLink.click();
      await adminLink.click();
    }
    expect(errors).toEqual([]);
  });
});
