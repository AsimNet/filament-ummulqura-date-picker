/*
 * Captures the README images (art/*.png, art/demo.gif, art/demo.mp4) from a
 * live Filament page that renders the pickers.
 *
 *   npm i playwright gifsicle && npx playwright install chromium
 *   BASE_URL=http://127.0.0.1:8000/picker-showcase node tools/capture-screenshots.mjs
 *
 * The page it points at is expected to render, inside a `.fi-section` card:
 *
 *   HijriDatePicker::make('birth_date')                    // Hijri UI, Gregorian storage
 *   HijriDateTimePicker::make('starts_at')->seconds(false)
 *   HijriDatePicker::make('id_date')->indicDigits()
 *   HijriDatePicker::make('hijri_column')
 *       ->displayCalendar(CalendarSystem::Gregorian)
 *       ->storageCalendar(CalendarSystem::Hijri)
 *
 * and to honour four query parameters: `locale`, `rtl`, `dark`, and `hero`
 * (the last one narrows the card to the first two fields). Any host app will
 * do — the images in this repo were taken from a scratch Livewire page.
 *
 * ffmpeg is used for the GIF/MP4 encode; gifsicle shrinks the GIF.
 */

import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const BASE_URL = process.env.BASE_URL;

if (!BASE_URL) {
  console.error('Set BASE_URL to a page rendering the pickers, e.g. http://127.0.0.1:8000/picker-showcase');
  process.exit(1);
}

const root = path.dirname(path.dirname(fileURLToPath(import.meta.url)));
const art = path.join(root, 'art');
const work = fs.mkdtempSync('/tmp/ummulqura-shots-');

fs.mkdirSync(art, { recursive: true });

const url = (query) => `${BASE_URL}${BASE_URL.includes('?') ? '&' : '?'}${query}`;
const browser = await chromium.launch();

/** Union of the form card and any open panel, padded — keeps shots tight. */
const clipOf = (page, pad = 28) =>
  page.evaluate((pad) => {
    const nodes = [
      document.querySelector('.fi-section'),
      ...document.querySelectorAll('.fi-fo-hijri-date-time-picker-panel'),
    ].filter((node) => node && (node.offsetParent !== null || node.classList.contains('fi-section')));
    const boxes = nodes.map((node) => node.getBoundingClientRect());
    const x = Math.max(0, Math.min(...boxes.map((b) => b.left)) - pad);
    const y = Math.max(0, Math.min(...boxes.map((b) => b.top)) - pad);

    return {
      x,
      y,
      width: Math.max(...boxes.map((b) => b.right)) + pad - x,
      height: Math.max(...boxes.map((b) => b.bottom)) + pad - y,
    };
  }, pad);

async function screenshot(name, { query, dark = false, field = 0, toggle = false, height = 900 }) {
  const page = await browser.newPage({
    viewport: { width: 1180, height },
    deviceScaleFactor: 2,
    colorScheme: dark ? 'dark' : 'light',
  });

  await page.goto(url(query), { waitUntil: 'networkidle' });
  await page.waitForTimeout(700);
  await page.locator('.fi-fo-date-time-picker-trigger').nth(field).click();
  await page.waitForTimeout(600);

  if (toggle) {
    await page
      .locator('.fi-fo-hijri-date-time-picker-panel').nth(field)
      .locator('button').filter({ hasText: /Hijri|Gregorian|هجري|ميلادي/ })
      .first()
      .click();
    await page.waitForTimeout(500);
  }

  await page.screenshot({ path: path.join(art, `${name}.png`), clip: await clipOf(page) });
  console.log(`${name}.png`);
  await page.close();
}

await screenshot('picker-en-light', { query: 'locale=en&hero=1', height: 760 });
await screenshot('picker-ar-dark', { query: 'locale=ar&rtl=1&dark=1', dark: true, field: 2 });
await screenshot('picker-gregorian', { query: 'locale=en', toggle: true });

/* The recording: open the picker, browse months, pick days in both
   calendars, then set a time. Playwright never draws the pointer, so a
   stand-in cursor follows the real mouse events. */
const [W, H] = [760, 680];
const context = await browser.newContext({
  viewport: { width: W, height: H },
  recordVideo: { dir: work, size: { width: W, height: H } },
  colorScheme: 'light',
});
const page = await context.newPage();

await page.goto(url('locale=en&hero=1'), { waitUntil: 'networkidle' });
await page.addStyleTag({
  content: `#capture-cursor {
    position: fixed; z-index: 99999; width: 20px; height: 20px; margin: -10px 0 0 -10px;
    border-radius: 9999px; background: rgba(17,24,39,.72); box-shadow: 0 0 0 4px rgba(17,24,39,.14);
    pointer-events: none; transition: transform .06s linear; top: 0; left: 0;
  }`,
});
await page.evaluate(() => {
  const cursor = document.createElement('div');
  cursor.id = 'capture-cursor';
  document.body.appendChild(cursor);
  addEventListener('mousemove', (event) => {
    cursor.style.transform = `translate(${event.clientX}px, ${event.clientY}px)`;
  }, true);
});

const pause = (ms) => page.waitForTimeout(ms);
const trigger = page.locator('.fi-fo-date-time-picker-trigger');
const panel = page.locator('.fi-fo-hijri-date-time-picker-panel');
const day = (n, index = 0) =>
  panel.nth(index).locator('.fi-fo-date-time-picker-calendar-day', { hasText: new RegExp(`^${n}$`) }).first();

const moveTo = async (locator, steps = 25) => {
  const box = await locator.boundingBox();
  await page.mouse.move(box.x + box.width / 2, box.y + box.height / 2, { steps });
};
const click = async (locator) => {
  await moveTo(locator);
  await pause(180);
  await locator.click({ force: true });
};
const ensureOpen = async (index) => {
  for (let attempt = 0; attempt < 3; attempt++) {
    if (await panel.nth(index).isVisible()) {
      return;
    }

    await click(trigger.nth(index));
    await pause(700);
  }
};

await page.mouse.move(W / 2, H - 60);
await pause(1200);

await click(trigger.nth(0));
await pause(900);

for (const n of [3, 11, 19]) {
  await moveTo(day(n), 18);
  await pause(650);
}

const monthSelect = panel.nth(0).locator('.fi-fo-date-time-picker-month-select');
await moveTo(monthSelect);
await pause(200);
await monthSelect.selectOption('9');
await pause(900);

await moveTo(day(15), 18);
await pause(500);
await day(15).click({ force: true });
await pause(1100);

await ensureOpen(0);
await click(panel.nth(0).locator('button').filter({ hasText: /Hijri|Gregorian/ }).first());
await pause(1100);
await moveTo(day(26), 18);
await pause(600);
await day(26).click({ force: true });
await pause(1200);

await page.keyboard.press('Escape');
await pause(500);
await ensureOpen(1);
await pause(400);

const hours = panel.nth(1).locator('.fi-fo-date-time-picker-time-inputs input').first();
await hours.waitFor({ state: 'visible', timeout: 5000 });
await moveTo(hours);
await pause(200);
await hours.fill('08');
await hours.dispatchEvent('input');
await pause(1000);
await moveTo(day(10, 1), 18);
await pause(400);
await day(10, 1).click({ force: true });
await pause(1600);

await context.close();
await browser.close();

const webm = path.join(work, fs.readdirSync(work).find((file) => file.endsWith('.webm')));
const gif = path.join(art, 'demo.gif');

execFileSync('ffmpeg', [
  '-v', 'error', '-y', '-i', webm,
  '-filter_complex',
  '[0:v] fps=12,scale=640:-1:flags=lanczos,split [a][b];[a] palettegen=max_colors=64:stats_mode=diff [p];'
  + '[b][p] paletteuse=dither=none:diff_mode=rectangle',
  '-loop', '0', gif,
]);

execFileSync('ffmpeg', [
  '-v', 'error', '-y', '-i', webm,
  '-vf', 'fps=25,scale=trunc(iw/2)*2:trunc(ih/2)*2,format=yuv420p',
  '-c:v', 'libx264', '-preset', 'slow', '-crf', '22', '-movflags', '+faststart',
  path.join(art, 'demo.mp4'),
]);

try {
  const { default: gifsicle } = await import('gifsicle');
  execFileSync(gifsicle, ['-O3', '--lossy=30', gif, '-o', gif]);
} catch {
  console.log('gifsicle not installed — skipping GIF optimisation');
}

for (const file of ['demo.gif', 'demo.mp4']) {
  console.log(`${file} ${(fs.statSync(path.join(art, file)).size / 1024).toFixed(0)} KB`);
}

fs.rmSync(work, { recursive: true, force: true });
