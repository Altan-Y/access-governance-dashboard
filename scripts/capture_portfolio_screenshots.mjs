import { chromium } from 'playwright';
import { mkdir } from 'node:fs/promises';

const baseUrl = process.env.ACCESSHUB_URL || 'http://127.0.0.1:8080';
await mkdir('screenshots', { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({
  viewport: { width: 1728, height: 900 },
  deviceScaleFactor: 1,
  colorScheme: 'light',
});
const page = await context.newPage();

async function preparePage() {
  await page.evaluate(() => {
    localStorage.setItem('accesshub-theme', 'light');
  });
}

await page.goto(baseUrl, { waitUntil: 'networkidle' });
await preparePage();
await page.reload({ waitUntil: 'networkidle' });
await page.screenshot({ path: 'screenshots/accesshub-login.png', fullPage: true });

await page.fill('input[name="email"]', 'altan@example.test');
await page.fill('input[name="password"]', 'demo123');
await Promise.all([
  page.waitForLoadState('networkidle'),
  page.click('button[type="submit"]'),
]);
await page.screenshot({ path: 'screenshots/accesshub-dashboard.png', fullPage: true });

await page.goto(`${baseUrl}/?view=roles`, { waitUntil: 'networkidle' });
await page.locator('.note-btn.edit').evaluateAll((buttons) => {
  buttons.forEach((button) => button.click());
});
await page.screenshot({ path: 'screenshots/accesshub-job-roles.png', fullPage: true });

await browser.close();
