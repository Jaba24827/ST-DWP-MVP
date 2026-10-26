/**
 * SL-DWP — system (end-to-end) test suite.
 *
 * One file, organised by the system-testing requirements of the capstone
 * plan. Each group id (SYS-xx) can be quoted in the test report.
 *
 *  SYS-01  Authentication and first factor
 *  SYS-02  Multi-factor authentication (email and SMS)
 *  SYS-03  Password policy (12+ characters, mixed)
 *  SYS-04  Role-based access control (UI, URL and API)
 *  SYS-05  Permission matrix and administration
 *  SYS-06  Notices and document repository (functional + file security)
 *  SYS-07  Audit logging
 *  SYS-08  Input security (SQL injection, XSS, CSP, headers)
 *  SYS-09  React presentation tier
 *  SYS-10  Usability: responsive layout, light/dark theme
 *  SYS-11  Accessibility (WCAG 2 A/AA automated checks)
 *  SYS-12  Performance budget
 *
 * Demo accounts and the shared password come from DemoDataSeeder.
 */
import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';
import { csrf, navLabels, PASSWORD, requestCode, signIn, signOut, submitPassword, USERS, watchPage } from './support/auth';
import { logMark, readMfaCode, resetDatabase } from './support/laravel';

const PDF = Buffer.from('%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF');
const STRONG = 'N3w-Secure#Pass26';

test.beforeAll(() => { if (!process.env.E2E_BASE_URL) resetDatabase(); });

/* ===================================================================== */
test.describe('SYS-01 Authentication and first factor', () => {
  test('the sign-in page is branded and offers the two-step form', async ({ page }) => {
    await page.goto('/login');
    await expect(page.getByRole('heading', { name: 'Sign in' })).toBeVisible();
    await expect(page.getByText('Step 1 of 2')).toBeVisible();
    await expect(page.getByLabel('Work email')).toBeVisible();
    await expect(page.getByLabel('Password')).toHaveAttribute('type', 'password');
  });

  for (const path of ['/dashboard', '/documents', '/users', '/roles', '/audit', '/account/security', '/app/dashboard']) {
    test(`anonymous visitors are sent to sign in: ${path}`, async ({ page }) => {
      await page.goto(path);
      await expect(page).toHaveURL(/\/login$/);
    });
  }

  test('a wrong password and an unknown address give the same message (no account enumeration)', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, 'Wrong!Passw0rd2026');
    const wrongPassword = await page.locator('.error-text').first().innerText();
    await submitPassword(page, 'nobody@stlukehaiti.org', 'Wrong!Passw0rd2026');
    const unknownUser = await page.locator('.error-text').first().innerText();
    expect(wrongPassword).toBe(unknownUser);
    await expect(page).toHaveURL(/\/login$/);
  });

  test('a deactivated account cannot sign in', async ({ page }) => {
    await submitPassword(page, USERS.inactive.email, PASSWORD);
    await expect(page.getByText('This account is not available')).toBeVisible();
  });

  test('repeated failures are throttled', async ({ page }) => {
    const email = `throttle.${Date.now()}@stlukehaiti.org`;
    for (let i = 0; i < 6; i++) await submitPassword(page, email, 'Wrong!Passw0rd2026');
    await expect(page.getByText(/Too many attempts/)).toBeVisible();
  });

  test('the password alone is not a session: protected pages bounce to the MFA step', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, PASSWORD);
    await expect(page).toHaveURL(/\/mfa$/);
    for (const path of ['/dashboard', '/documents', '/directory']) {
      await page.goto(path);
      await expect(page).toHaveURL(/\/mfa$/);
    }
  });

  test('signing out ends the session', async ({ page }) => {
    await signIn(page, 'staff');
    await signOut(page);
    await page.goto('/dashboard');
    await expect(page).toHaveURL(/\/login$/);
  });
});

/* ===================================================================== */
test.describe('SYS-02 Multi-factor authentication', () => {
  test('email channel: code delivered, accepted, session established', async ({ page }) => {
    await signIn(page, 'admin', 'email');
    await expect(page.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeVisible();
  });

  test('SMS channel: code delivered by the SMS driver and accepted', async ({ page }) => {
    await signIn(page, 'manager', 'sms');
    await expect(page.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeVisible();
  });

  test('destinations are masked on the verification screen', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, PASSWORD);
    await expect(page.locator('.mono').filter({ hasText: '••' }).first()).toBeVisible();
    await expect(page.getByText(USERS.staff.email)).toHaveCount(0);
  });

  test('a wrong code is refused with the attempts remaining', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, PASSWORD);
    await requestCode(page);
    await page.locator('#code').fill('000000');
    await page.locator('#verifySubmit').click();
    await expect(page.getByText(/not correct|attempts left/)).toBeVisible();
    await expect(page).toHaveURL(/\/mfa$/);
  });

  test('the attempt limit locks the challenge', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, PASSWORD);
    const real = await requestCode(page);
    const wrong = real === '111111' ? '222222' : '111111';
    for (let i = 0; i < 6; i++) {
      await page.locator('#code').fill(wrong);
      await page.locator('#verifySubmit').click();
    }
    await expect(page.getByText(/Too many incorrect codes|Request a new code/)).toBeVisible();
    // Even the right code no longer works on a locked challenge.
    await page.locator('#code').fill(real);
    await page.locator('#verifySubmit').click();
    await expect(page).toHaveURL(/\/mfa$/);
  });

  test('requesting a new code invalidates the previous one', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, PASSWORD);
    const first = await requestCode(page);
    const mark = logMark();
    await page.getByRole('button', { name: 'Send a new code' }).click();
    let second: string | null = null;
    await expect.poll(() => (second = readMfaCode(mark))).not.toBeNull();
    test.skip(first === second, 'astronomically unlikely code collision');
    await page.locator('#code').fill(first);
    await page.locator('#verifySubmit').click();
    await expect(page).toHaveURL(/\/mfa$/);
    await page.locator('#code').fill(second!);
    await page.locator('#verifySubmit').click();
    await expect(page).toHaveURL(/dashboard/);
  });

  test('the code field accepts digits only', async ({ page }) => {
    await submitPassword(page, USERS.staff.email, PASSWORD);
    await requestCode(page);
    await page.locator('#code').fill('12ab34');
    await expect(page.locator('#code')).toHaveValue(/^\d*$/);
  });
});

/* ===================================================================== */
test.describe.serial('SYS-03 Password policy', () => {
  test.beforeAll(() => { if (!process.env.E2E_BASE_URL) resetDatabase(); });

  const rule = (page: Page, name: string) => page.locator(`#pwRules li[data-rule="${name}"]`);

  test('the rule list reacts to input and the submit button stays disabled until every rule passes', async ({ page }) => {
    await signIn(page, 'staff2');
    await page.goto('/account/security');
    const submit = page.locator('#pwSubmit');
    await expect(submit).toBeDisabled();

    const cases: Array<[string, string]> = [
      ['Abcdefghi1!', 'len'],      // 11 chars
      ['abcdefghij1!', 'upper'],
      ['ABCDEFGHIJ1!', 'lower'],
      ['Abcdefghijk!', 'digit'],
      ['Abcdefghijk1', 'symbol'],
    ];
    for (const [value, failing] of cases) {
      await page.locator('#password').fill(value);
      await page.locator('#password_confirmation').fill(value);
      await expect(rule(page, failing), `${value} should fail ${failing}`).not.toHaveClass(/is-met/);
      await expect(submit).toBeDisabled();
    }

    await page.locator('#password').fill(STRONG);
    await page.locator('#password_confirmation').fill(STRONG + 'x');
    await expect(rule(page, 'match')).not.toHaveClass(/is-met/);
    await expect(submit).toBeDisabled();

    await page.locator('#password_confirmation').fill(STRONG);
    for (const r of ['len', 'upper', 'lower', 'digit', 'symbol', 'match']) await expect(rule(page, r)).toHaveClass(/is-met/);
    await expect(submit).toBeEnabled();
  });

  test('the server enforces the policy even when the browser check is bypassed', async ({ page }) => {
    await signIn(page, 'staff2');
    await page.goto('/account/security');
    const token = await csrf(page);
    for (const weak of ['short1!', 'alllowercase123!', 'NoSymbolsHere123']) {
      const res = await page.request.post('/account/password', {
        form: { _method: 'PUT', _token: token, current_password: PASSWORD, password: weak, password_confirmation: weak },
      });
      expect(await res.text()).toMatch(/flash--error|error-text/);
    }
  });

  test('a compliant change succeeds; the old password stops working; the new one signs in', async ({ page }) => {
    await signIn(page, 'staff2');
    await page.goto('/account/security');
    await page.locator('#current_password').fill(PASSWORD);
    await page.locator('#password').fill(STRONG);
    await page.locator('#password_confirmation').fill(STRONG);
    await page.locator('#pwSubmit').click();
    await expect(page.locator('.flash--ok')).toBeVisible();

    await signOut(page);
    await submitPassword(page, USERS.staff2.email, PASSWORD);
    await expect(page.locator('.error-text').first()).toBeVisible();
    await signIn(page, 'staff2', 'email', STRONG);
  });

  test('reusing a recent password is refused', async ({ page }) => {
    await signIn(page, 'staff2', 'email', STRONG);
    await page.goto('/account/security');
    await page.locator('#current_password').fill(STRONG);
    await page.locator('#password').fill(PASSWORD);
    await page.locator('#password_confirmation').fill(PASSWORD);
    await page.locator('#pwSubmit').click();
    await expect(page.locator('.flash--error, .error-text').first()).toBeVisible();
  });
});

/* ===================================================================== */
test.describe('SYS-04 Role-based access control', () => {
  const NAV: Record<string, string[]> = {
    admin:   ['Dashboard', 'Notices', 'Directory', 'Documents', 'Users', 'Roles', 'Audit log', 'Security'],
    manager: ['Dashboard', 'Notices', 'Directory', 'Documents', 'Security'],
    staff:   ['Dashboard', 'Notices', 'Directory', 'Documents', 'Security'],
    auditor: ['Dashboard', 'Notices', 'Directory', 'Documents', 'Audit log', 'Security'],
  };
  for (const [who, expected] of Object.entries(NAV)) {
    test(`${who}: the sidebar contains exactly the permitted areas`, async ({ page }) => {
      await signIn(page, who as keyof typeof USERS, who === 'manager' ? 'sms' : 'email');
      expect(await navLabels(page)).toEqual(expected);
    });
  }

  test('staff: URLs outside the role are refused by the server, not merely hidden', async ({ page }) => {
    await signIn(page, 'staff');
    for (const path of ['/users', '/roles', '/audit']) {
      const res = await page.goto(path);
      expect(res?.status(), path).toBe(403);
    }
  });

  test('staff: a crafted POST to a publish-only endpoint is refused', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/notices');
    const res = await page.request.post('/notices', {
      form: { _token: await csrf(page), title: 'Injected', body: 'Not allowed', audience: 'all' },
    });
    expect(res.status()).toBe(403);
  });

  test('staff: a restricted document cannot be opened by guessing its address', async ({ browser, page }) => {
    // Admin discovers the address of a restricted document...
    const adminCtx = await browser.newContext();
    const adminPage = await adminCtx.newPage();
    await signIn(adminPage, 'admin');
    await adminPage.goto('/documents');
    const href = await adminPage.locator('tr', { hasText: 'Salary scale review' }).getByRole('link', { name: 'Open' }).getAttribute('href');
    await adminCtx.close();
    expect(href).toBeTruthy();

    // ...staff cannot use it, and never saw it listed.
    await signIn(page, 'staff');
    await page.goto('/documents');
    await expect(page.getByText('Salary scale review')).toHaveCount(0);
    await expect(page.getByText(/filtered out of the query for your role/)).toBeVisible();
    const res = await page.request.get(href!);
    expect(res.status()).toBe(403);
  });

  test('role capabilities: upload and publish are visible only where granted', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/documents');
    await expect(page.getByRole('button', { name: 'Upload a document' })).toHaveCount(0);
    await page.goto('/notices');
    await expect(page.getByRole('button', { name: 'Publish a notice' })).toHaveCount(0);
  });

  test('API: unauthenticated 401, MFA-pending 423, staff 403 on admin data, /api/me lists permissions', async ({ page, playwright }) => {
    const anon = await playwright.request.newContext({ baseURL: test.info().project.use.baseURL });
    expect((await anon.get('/api/me', { headers: { Accept: 'application/json' } })).status()).toBe(401);
    await anon.dispose();

    await submitPassword(page, USERS.staff.email, PASSWORD);
    const apiHeaders = {
      Accept: 'application/json',
      Origin: 'http://127.0.0.1:8001',
       Referer: 'http://127.0.0.1:8001/',
    };

    const pendingResponse = await page.request.get('/api/me', {
      headers: apiHeaders,
    });

    expect(pendingResponse.status()).toBe(423);

    const code = await requestCode(page);
    await page.locator('#code').fill(code);
    await page.locator('#verifySubmit').click();
    await expect(page).toHaveURL(/dashboard/);

    const me = await page.request.get('/api/me', { headers: apiHeaders, });
    expect(me.status()).toBe(200);
    const body = await me.json();
    expect(body.role).toBe('Staff');
    expect(body.permissions).toEqual(expect.arrayContaining(['dashboard.view', 'documents.view']));
    expect(body.permissions).not.toContain('audit.view');
    expect((await page.request.get('/api/audit', { headers: apiHeaders, })).status()).toBe(403);
  });
});

/* ===================================================================== */
test.describe.serial('SYS-05 Permission matrix and administration', () => {
  test.beforeAll(() => { if (!process.env.E2E_BASE_URL) resetDatabase(); });

  test('admin grants Staff the upload permission; it persists and reaches the staff session', async ({ browser, page }) => {
    await signIn(page, 'admin');
    await page.goto('/roles');
    const box = page.getByRole('checkbox', { name: 'Staff holds documents.upload' });
    await expect(box).not.toBeChecked();
    await box.check();
    await expect(page.locator('.flash--ok')).toBeVisible();
    await page.reload();
    await expect(page.getByRole('checkbox', { name: 'Staff holds documents.upload' })).toBeChecked();

    const ctx = await browser.newContext();
    const staff = await ctx.newPage();
    await signIn(staff, 'staff');
    await staff.goto('/documents');
    await expect(staff.getByRole('button', { name: 'Upload a document' })).toBeVisible();
    await ctx.close();
  });

  test('the self-lockout guard stops an administrator removing roles.manage from their own role', async ({ page }) => {
    await signIn(page, 'admin');
    await page.goto('/roles');
    const box = page.getByRole('checkbox', { name: 'Administrator holds roles.manage' });
    await expect(box).toBeChecked();
    page.on('response', async (response) => {
      if (/\/roles(?:\/\d+)?(?:\?.*)?$/.test(new URL(response.url()).pathname)) {
        console.log(
          '[ROLE RESPONSE]',
          response.request().method(),
          response.status(),
          new URL(response.url()).pathname,
          'Location:',
          response.headers()['location'] ?? '(none)'
        );
      }
    });

    page.on('requestfailed', (request) => {
      if (new URL(request.url()).pathname.startsWith('/roles')) {
        console.log(
          '[ROLE REQUEST FAILED]',
          request.method(),
          new URL(request.url()).pathname,
          request.failure()?.errorText
        );
      }
    });
    await box.uncheck();
    await expect(page.locator('.flash--error, .error-text').first()).toBeVisible();
    await page.reload();
    await expect(page.getByRole('checkbox', { name: 'Administrator holds roles.manage' })).toBeChecked();
    expect((await page.goto('/roles'))?.status()).toBe(200);
  });

  test('admin deactivates an account and that person can no longer sign in', async ({ browser, page }) => {
    await signIn(page, 'admin');
    await page.goto('/users');
    await page.locator('tr', { hasText: 'f.delva@stlukehaiti.org' }).getByRole('button', { name: 'Deactivate' }).click();
    await expect(page.locator('tr', { hasText: 'f.delva@stlukehaiti.org' }).getByText('deactivated')).toBeVisible();

    const ctx = await browser.newContext();
    const other = await ctx.newPage();
    await submitPassword(other, 'f.delva@stlukehaiti.org', PASSWORD);
    await expect(other.getByText('This account is not available')).toBeVisible();
    await ctx.close();
  });
});

/* ===================================================================== */
test.describe.serial('SYS-06 Notices and document repository', () => {
  test.beforeAll(() => { if (!process.env.E2E_BASE_URL) resetDatabase(); });

  test('a manager publishes a notice that other staff then see', async ({ browser, page }) => {
    const title = `Reopening notice ${Date.now()}`;
    await signIn(page, 'manager', 'sms');
    await page.goto('/notices');
    await page.getByRole('button', { name: 'Publish a notice' }).click();
    await page.locator('#newNotice #title').fill(title);
    await page.locator('#newNotice #body').fill('Please confirm availability.');
    await page.locator('#newNotice').getByRole('button', { name: 'Publish' }).click();
    await expect(page.getByText(title)).toBeVisible();

    const ctx = await browser.newContext();
    const staff = await ctx.newPage();
    await signIn(staff, 'staff');
    await staff.goto('/notices');
    await expect(staff.getByText(title)).toBeVisible();
    await ctx.close();
  });


  test('notice text is escaped: markup in a title is displayed, never executed', async ({ page }) => {
    const dialogs: string[] = [];
    page.on('dialog', async (dialog) => {
      dialogs.push(dialog.message());
      await dialog.dismiss();
    });

    const payload = '<img src=x onerror=alert(1)><script>alert(2)</script>';

    await signIn(page, 'manager', 'sms');
    await page.goto('/notices');
    await page.getByRole('button', { name: 'Publish a notice' }).click();
    await page.locator('#newNotice #title').fill(payload);
    await page.locator('#newNotice #body').fill('xss probe');
    await page.locator('#newNotice').getByRole('button', { name: 'Publish' }).click();

    // strip_tags() removes HTML tags before storage; the remaining text is visible.
    await expect(page.getByText('alert(2)', { exact: true })).toBeVisible();

    // The payload must not create an executable script or injected image.
    expect(dialogs).toEqual([]);
    await expect(page.locator('.page img[src="x"]')).toHaveCount(0);
    await expect(page.locator('.page script')).toHaveCount(0);
  });



  test('a manager uploads a restricted PDF; access is restricted and the upload is audited', async ({ browser, page }) => {
    const title = `Contract draft ${Date.now()}`;

    // A sector manager may upload a restricted document but cannot view it.
    await signIn(page, 'manager', 'sms');
    await page.goto('/documents');
    await page.getByRole('button', { name: 'Upload a document' }).click();
    await page.locator('#uploadForm #title').fill(title);
    await page.locator('#upload_classification').selectOption('restricted');
    await page.locator('#file').setInputFiles({
      name: 'contract.pdf',
      mimeType: 'application/pdf',
      buffer: PDF,
    });
    await page.locator('#uploadForm')
      .getByRole('button', { name: /upload|save/i })
      .last()
      .click();

    await expect(page.locator('.flash--ok')).toContainText(title);
    await expect(page.locator('tbody tr', { hasText: title })).toHaveCount(0);

    // An administrator with documents.view.restricted can see the upload.
    const ctx = await browser.newContext();
    try {
      const admin = await ctx.newPage();
      await signIn(admin, 'admin');
      await admin.goto('/documents');

      const row = admin.locator('tbody tr', { hasText: title });
      await expect(row).toBeVisible();
      await expect(row.locator('.tag--deny')).toHaveText('restricted');

      // Verify the upload event is visible in the audit log.
      await admin.goto(`/audit?q=${encodeURIComponent(title)}`);
      await expect(
        admin.locator('tbody tr', { hasText: title }).first()
      ).toBeVisible();
    } finally {
      await ctx.close();
    }
  });

  test('uploads are validated by content, not by file name', async ({ page }) => {
    await signIn(page, 'manager', 'sms');
    await page.goto('/documents');
    for (const file of [
      { name: 'shell.pdf', mimeType: 'application/pdf', buffer: Buffer.from('<?php system($_GET["c"]); ?>') },
      { name: 'payload.exe', mimeType: 'application/octet-stream', buffer: Buffer.from('MZ\x90\x00') },
    ]) {
      await page.getByRole('button', { name: 'Upload a document' }).click();
      await page.locator('#uploadForm #title').fill(`Bad ${file.name}`);
      await page.locator('#upload_classification').selectOption('internal');
      await page.locator('#file').setInputFiles(file);
      await page.locator('#uploadForm').getByRole('button', { name: /upload|save/i }).last().click();
      await expect(page.locator('.flash--error').first()).toBeVisible();
      await expect(page.locator('tr', { hasText: `Bad ${file.name}` })).toHaveCount(0);
      await page.goto('/documents');
    }
  });

  test('search and classification filters narrow the list', async ({ page }) => {
    await signIn(page, 'admin');
    await page.goto('/documents');
    await page.locator('#q').fill('handbook');
    await page.locator('#q').press('Enter');
    await expect(page.locator('tbody tr')).toHaveCount(1);
    await page.goto('/documents');
    await page.locator('select#classification').selectOption('restricted');
    await expect(page).toHaveURL(/classification=restricted/);
    await expect(page.locator('tbody .tag--deny').first()).toBeVisible();
    await expect(page.locator('tbody .tag--ok')).toHaveCount(0);
  });

  test('the staff directory lists people and filters by sector', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/directory');
    await expect(page.getByText('Micheline Pierre')).toBeVisible();
    await page.locator('select[name="sector_id"]').selectOption({ label: 'Health' });
    await expect(page).toHaveURL(/sector_id=/);
    await expect(page.getByText('Gerald Moise')).toHaveCount(0);
  });
});

/* ===================================================================== */
test.describe('SYS-07 Audit logging', () => {
  test('a denied request by staff is recorded and visible to the administrator and auditor', async ({ browser, page }) => {
    await signIn(page, 'staff');
    expect((await page.goto('/users'))?.status()).toBe(403);

    for (const who of ['admin', 'auditor'] as const) {
      const ctx = await browser.newContext();
      const p = await ctx.newPage();
      await signIn(p, who);
      await p.goto('/audit?kind=denied&q=users.manage');
      const row = p.locator('tbody tr', { hasText: 'users.manage' }).first();
      await expect(row).toBeVisible();
      await expect(row.locator('.tag--deny')).toHaveText('denied');
      await ctx.close();
    }
  });

  test('sign-in steps are logged (password verified, MFA challenged, MFA verified)', async ({ page }) => {
    await signIn(page, 'admin');
    await page.goto('/audit?kind=auth');
    for (const action of ['auth.password.verified', 'auth.mfa.challenged', 'auth.mfa.verified']) {
      await expect(page.locator('tbody tr', { hasText: action }).first()).toBeVisible();
    }
  });

  test('failed sign-ins are logged as denied', async ({ browser, page }) => {
    await submitPassword(page, USERS.staff.email, 'Wrong!Passw0rd2026');
    const ctx = await browser.newContext();
    const admin = await ctx.newPage();
    await signIn(admin, 'admin');
    await admin.goto('/audit?kind=denied&q=auth.login');
    await expect(admin.locator('tbody tr', { hasText: 'auth.login' }).first()).toBeVisible();
    await ctx.close();
  });

  test('the log offers no way to edit or delete entries', async ({ page }) => {
    await signIn(page, 'admin');
    await page.goto('/audit');
    await expect(page.locator('tbody').getByRole('button')).toHaveCount(0);
    const res = await page.request.delete('/audit/1', { headers: { 'X-CSRF-TOKEN': await csrf(page) } });
    expect([404, 405]).toContain(res.status());
  });
});

/* ===================================================================== */
test.describe('SYS-08 Input security', () => {
  const SQLI = ["' OR '1'='1' --", "admin' --", "'; DROP TABLE users; --", '" OR ""="', '1; SELECT SLEEP(5)'];

  for (const payload of SQLI) {
    test(`login form: SQL injection payload is just a failed sign-in (${payload})`, async ({ page }) => {
      await submitPassword(page, payload, payload);
      await expect(page).toHaveURL(/\/login$/);
      await expect(page.locator('.error-text').first()).toBeVisible();
      await expect(page.locator('body')).not.toContainText(/SQLSTATE|syntax error|Illuminate/i);
    });
  }

  test('search inputs treat SQL as text and the application keeps working', async ({ page }) => {
    await signIn(page, 'admin');
    for (const path of ['/documents', '/directory', '/audit']) {
      for (const payload of SQLI) {
        const res = await page.goto(`${path}?q=${encodeURIComponent(payload)}`);
        expect(res?.status(), `${path} ${payload}`).toBe(200);
        await expect(page.locator('body')).not.toContainText(/SQLSTATE|syntax error/i);
      }
    }
    // The users table survived the DROP TABLE attempt.
    await page.goto('/users');
    await expect(page.getByText(USERS.staff.email)).toBeVisible();
  });

  test('security headers and a nonce-based CSP are present', async ({ page }) => {
    const res = await page.goto('/login');
    const h = res!.headers();
    expect(h['x-frame-options']).toBe('DENY');
    expect(h['x-content-type-options']).toBe('nosniff');
    expect(h['referrer-policy']).toBe('same-origin');
    expect(h['content-security-policy']).toContain("default-src 'self'");
    expect(h['content-security-policy']).toContain("frame-ancestors 'none'");
    expect(h['content-security-policy']).not.toContain("script-src 'self' 'unsafe-inline'");
  });

  test('no page raises a CSP violation or script error', async ({ page }) => {
    const problems = watchPage(page);
    await page.goto('/login');
    await signIn(page, 'admin');
    for (const path of ['/dashboard', '/notices', '/directory', '/documents', '/users', '/roles', '/audit', '/account/security']) {
      await page.goto(path);
      await page.waitForLoadState('networkidle');
    }
    expect(problems).toEqual([]);
  });

  test('state-changing requests without a CSRF token are rejected', async ({ page }) => {
    await signIn(page, 'admin');
    const res = await page.request.post('/notices', { form: { title: 'x', body: 'y', audience: 'all' } });
    expect(res.status()).toBe(419);
  });

  test('session cookie is HttpOnly and SameSite', async ({ page, context }) => {
    await signIn(page, 'staff');
    const cookie = (await context.cookies()).find((c) => /session/i.test(c.name));
    expect(cookie?.httpOnly).toBe(true);
    expect(['Lax', 'Strict']).toContain(cookie?.sameSite);
  });

  test('the phone number is not exposed in any page the user can load', async ({ page }) => {
    await signIn(page, 'admin');
    await page.goto('/directory');
    await expect(page.locator('body')).not.toContainText('+509 3400');
    await page.goto('/users');
    await expect(page.locator('body')).not.toContainText('+509 3400');
  });
});

/* ===================================================================== */
test.describe('SYS-09 React presentation tier', () => {
  test('the React shell renders navigation from /api/me permissions', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/app/dashboard');
    const nav = page.locator('aside.sidebar');
    await expect(nav.getByText('Documents')).toBeVisible();
    await expect(nav.getByText('Audit log')).toHaveCount(0);
  });

  test('React route guard: a staff user is redirected away from /app/audit', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/app/audit');
    await expect(page).toHaveURL(/\/app\/dashboard$/);
  });

  test('React lists documents through the API, restricted ones filtered server-side', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/app/documents');
    await expect(page.getByText('Staff handbook 2026')).toBeVisible();
    await expect(page.getByText('Salary scale review')).toHaveCount(0);
  });

  test('React audit view works for the auditor', async ({ page }) => {
    await signIn(page, 'auditor');
    await page.goto('/app/audit');
    await expect(page.locator('table')).toBeVisible();
  });
});

/* ===================================================================== */
test.describe('SYS-10 Usability: responsive layout and theme', () => {
  const VIEWPORTS = [
    { name: 'mobile', width: 390, height: 844 },
    { name: 'tablet', width: 820, height: 1180 },
    { name: 'laptop', width: 1280, height: 800 },
    { name: 'desktop', width: 1920, height: 1080 },
  ];

  for (const vp of VIEWPORTS) {
    test(`${vp.name} (${vp.width}px): no horizontal page scroll on any main screen`, async ({ page }) => {
      await page.setViewportSize({ width: vp.width, height: vp.height });
      await signIn(page, 'admin');
      for (const path of ['/dashboard', '/documents', '/users', '/roles', '/audit', '/account/security']) {
        await page.goto(path);
        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
        expect(overflow, `${path} at ${vp.width}px`).toBeLessThanOrEqual(1);
      }
    });
  }

  test('mobile: the sidebar is an off-canvas drawer opened by the menu button and closed with Escape', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await signIn(page, 'staff');
    await expect(page.locator('.app')).not.toHaveClass(/is-drawer/);
    await page.locator('#toggleNav').click();
    await expect(page.locator('.app')).toHaveClass(/is-drawer/);
    await expect(page.locator('.sidebar')).toBeInViewport();
    await page.keyboard.press('Escape');
    await expect(page.locator('.app')).not.toHaveClass(/is-drawer/);
  });

  test('desktop: the sidebar collapses and expands', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await signIn(page, 'staff');
    await page.locator('#toggleNav').click();
    await expect(page.locator('.app')).toHaveClass(/is-collapsed/);
    await page.locator('#toggleNav').click();
    await expect(page.locator('.app')).not.toHaveClass(/is-collapsed/);
  });

  test('theme toggle switches light/dark, remembers the choice, and dark text is white or lighter', async ({ page }) => {
    await signIn(page, 'staff');
    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/dashboard');
    const html = page.locator('html');
    await expect(html).toHaveAttribute('data-theme', 'light');

    await page.locator('#themeBtn').click();
    await expect(html).toHaveAttribute('data-theme', 'dark');
    const rgb = await page.evaluate(() => getComputedStyle(document.body).color.match(/\d+/g)!.map(Number));
    expect(Math.min(...rgb.slice(0, 3))).toBeGreaterThanOrEqual(230);          // white or very light text
    const bg = await page.evaluate(() => getComputedStyle(document.body).backgroundColor.match(/\d+/g)!.map(Number));
    expect(Math.max(...bg.slice(0, 3))).toBeLessThan(60);                      // dark surface

    await page.reload();
    await expect(html).toHaveAttribute('data-theme', 'dark');                  // persisted
    await page.locator('#themeBtn').click();
    await expect(html).toHaveAttribute('data-theme', 'light');
  });

  test('brand: the page heading is green and the sidebar is white in light mode', async ({ page }) => {
    await signIn(page, 'staff');
    await page.emulateMedia({ colorScheme: 'light' });
    await page.goto('/dashboard');
    const [r, g, b] = await page.locator('h1').first().evaluate((el) => getComputedStyle(el).color.match(/\d+/g)!.map(Number));
    expect(g).toBeGreaterThan(r);
    expect(g).toBeGreaterThan(b);
    expect(await page.locator('.sidebar').evaluate((el) => getComputedStyle(el).backgroundColor)).toBe('rgb(255, 255, 255)');
  });

  test('keyboard: the skip link is the first tab stop and reaches the main content', async ({ page }) => {
    await signIn(page, 'staff');
    await page.goto('/dashboard');
    await page.keyboard.press('Tab');
    const skip = page.getByRole('link', { name: 'Skip to main content' });
    await expect(skip).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/#main$/);
  });
});

/* ===================================================================== */
test.describe('SYS-11 Accessibility (axe-core, WCAG 2 A/AA)', () => {
  const blocking = (violations: any[]) =>
    violations.filter((v) => v.impact === 'serious' || v.impact === 'critical')
              .map((v) => `${v.id}: ${v.help} (${v.nodes.length} node${v.nodes.length > 1 ? 's' : ''})`);

  test('sign-in page', async ({ page }) => {
    await page.goto('/login');
    const r = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa']).analyze();
    expect(blocking(r.violations)).toEqual([]);
  });

  for (const theme of ['light', 'dark'] as const) {
    test(`signed-in screens in ${theme} mode`, async ({ page }) => {
      await signIn(page, 'admin');

      // Set the preference before application scripts run on each navigation.
      await page.emulateMedia({ colorScheme: 'light' });

      await page.evaluate((mode) => {
        localStorage.setItem('sldwp.theme', mode);
      }, theme);

      for (const path of [
        '/dashboard',
        '/documents',
        '/users',
        '/roles',
        '/audit',
        '/account/security',
      ]) {

      await page.goto(path);

      await page.evaluate((mode) => {
        localStorage.setItem('sldwp.theme', mode);
        document.documentElement.setAttribute('data-theme', mode);
      }, theme);

      await expect(page.locator('html')).toHaveAttribute('data-theme', theme);

        const result = await new AxeBuilder({ page })
          .withTags(['wcag2a', 'wcag2aa'])
          .analyze();

        // Temporary diagnostic output: identify each failing element and why.
        console.log(`\nAXE DETAILS: ${theme} mode — ${path}`);
        for (const violation of result.violations) {
          if (violation.impact === 'serious' || violation.impact === 'critical') {
            console.log(JSON.stringify({
              id: violation.id,
              impact: violation.impact,
              help: violation.help,
              nodes: violation.nodes.map((node) => ({
                target: node.target,
                html: node.html,
                summary: node.failureSummary,
                checks: [...node.any, ...node.all, ...node.none].map((check) => ({
                  id: check.id,
                  message: check.message,
                  data: check.data,
                })),
              })),
            }, null, 2));
          }
        }

        expect(blocking(result.violations), path).toEqual([]);
      }
    });
  }

  test('every form control on the main forms has an accessible name', async ({ page }) => {
    await signIn(page, 'admin');
    for (const path of ['/documents', '/notices', '/account/security', '/audit']) {
      await page.goto(path);
      const unnamed = await page.evaluate(() =>
        [...document.querySelectorAll('input:not([type=hidden]), select, textarea')]
          .filter((el: any) => !el.labels?.length && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby'))
          .map((el: any) => el.name || el.id));
      expect(unnamed, path).toEqual([]);
    }
  });
});

/* ===================================================================== */
test.describe('SYS-12 Performance budget', () => {
  test('main screens load within the budget on a local server', async ({ page }) => {
    await signIn(page, 'admin');
    for (const path of ['/dashboard', '/documents', '/audit']) {
      const t0 = Date.now();
      await page.goto(path, { waitUntil: 'load' });
      const elapsed = Date.now() - t0;
      expect(elapsed, `${path} took ${elapsed} ms`).toBeLessThan(3000);
    }
  });

  test('a navigation transfers a modest amount of data', async ({ page }) => {
    await signIn(page, 'admin');
    let bytes = 0;
    page.on('response', async (r) => { const l = r.headers()['content-length']; if (l) bytes += Number(l); });
    await page.goto('/documents', { waitUntil: 'networkidle' });
    expect(bytes).toBeLessThan(1_500_000);
  });
});
