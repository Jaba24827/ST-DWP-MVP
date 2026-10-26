import { expect, type Page } from '@playwright/test';
import { logMark, readMfaCode } from './laravel';

export const PASSWORD = process.env.E2E_PASSWORD ?? 'Demo!Passw0rd2026';

export const USERS = {
  admin:   { email: 'j.dorsainvil@stlukehaiti.org',  name: 'Jaebets Dorsainvil', role: 'Administrator' },
  manager: { email: 'm.pierre@stlukehaiti.org',      name: 'Micheline Pierre',   role: 'Sector manager' },
  staff:   { email: 'r.jeanbaptiste@stlukehaiti.org', name: 'Ronald Jean-Baptiste', role: 'Staff' },
  auditor: { email: 'c.altidor@stlukehaiti.org',     name: 'Carline Altidor',    role: 'Auditor' },
  staff2:  { email: 'n.charles@stlukehaiti.org',      name: 'Nadege Charles',     role: 'Staff' },
  inactive:{ email: 'w.etienne@stlukehaiti.org',     name: 'Wilner Etienne',     role: 'Staff' },
} as const;
export type Who = keyof typeof USERS;

/** Step 1: email + password. Stops on whatever page the server returns. */
export async function submitPassword(page: Page, email: string, password: string) {
  await page.goto('/login');
  await page.locator('#email').fill(email);
  await page.locator('#password').fill(password);

  await page.getByRole('button', { name: 'Continue' }).click();

  // Wait for the login request and page navigation to settle.
  await page.waitForLoadState('networkidle').catch(() => {});

  console.log('Login URL:', page.url());
  console.log(
    'Login page text:',
    (await page.locator('body').innerText()).slice(0, 2000)
  );
}

/** Step 2a: choose a channel and send; returns the code read back from the log. */
export async function requestCode(page: Page, channel: 'email' | 'sms' = 'email'): Promise<string> {
  await page.locator(`input[name="channel"][value="${channel}"]`).check();
  const mark = logMark();
  await page.getByRole('button', { name: 'Send the code' }).click();
  await expect(page.locator('#code')).toBeVisible();
  let code: string | null = null;
  await expect.poll(() => (code = readMfaCode(mark)), { timeout: 10_000 }).not.toBeNull();
  return code!;
}

/** Full two-factor sign-in. */
export async function signIn(page: Page, who: Who, channel: 'email' | 'sms' = 'email', password = PASSWORD) {
  await submitPassword(page, USERS[who].email, password);
  await expect(page).toHaveURL(/\/mfa$/);
  const code = await requestCode(page, channel);
  await page.locator('#code').fill(code);
  await page.locator('#verifySubmit').click();
  await expect(page).not.toHaveURL(/\/(mfa|login)$/);
}

export async function signOut(page: Page) {
  await page.getByRole('button', { name: 'Sign out' }).click();
  await expect(page).toHaveURL(/\/login$/);
}

export async function csrf(page: Page): Promise<string> {
  return page.locator('meta[name="csrf-token"]').getAttribute('content').then((v) => v ?? '');
}

export const navLabels = (page: Page) =>
  page.locator('.sidebar nav .nav-item').allInnerTexts().then((a) => a.map((s) => s.trim()));

/** Collects CSP violations, page errors and console errors for the page's lifetime. */
export function watchPage(page: Page) {
  const problems: string[] = [];
  page.on('pageerror', (e) => problems.push(`pageerror: ${e.message}`));
  page.on('console', (m) => {
    if (m.type() === 'error') problems.push(`console: ${m.text()}`);
  });
  return problems;
}
