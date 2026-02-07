/**
 * Shared login helper for authenticated e2e tests.
 *
 * Credentials are read from environment variables:
 *   TEST_SA_USERNAME / TEST_SA_PASSWORD   – Super Admin account
 *   TEST_USER_USERNAME / TEST_USER_PASSWORD – Regular test user account
 */

async function loginAsSA(page) {
  const user = process.env.TEST_SA_USERNAME;
  const pass = process.env.TEST_SA_PASSWORD;
  if (!user || !pass) {
    throw new Error('TEST_SA_USERNAME and TEST_SA_PASSWORD environment variables must be set');
  }
  await login(page, user, pass);
}

async function loginAsTestUser(page) {
  const user = process.env.TEST_USER_USERNAME;
  const pass = process.env.TEST_USER_PASSWORD;
  if (!user || !pass) {
    throw new Error('TEST_USER_USERNAME and TEST_USER_PASSWORD environment variables must be set');
  }
  await login(page, user, pass);
}

async function login(page, username, password) {
  await page.goto('/login/index.php');
  await page.fill('#user_name', username);
  await page.fill('#user_password', password);
  await page.click('input[type="submit"]');
  // Wait for redirect after successful login
  await page.waitForURL(/(?!.*login)/);
}

async function logout(page) {
  await page.goto('/login/index.php?logout');
}

module.exports = { login, loginAsSA, loginAsTestUser, logout };
