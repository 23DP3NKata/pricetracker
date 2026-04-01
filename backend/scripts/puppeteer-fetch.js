#!/usr/bin/env node

const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const MAX_RETRIES = 3;
const RETRY_DELAY_MS = 3000;

const BAN_SIGNATURES = [
  'access denied',
  'blocked',
  'captcha',
  'are you a robot',
  'unusual traffic',
  'cloudflare',
  'ddos-guard',
  'please verify',
  'too many requests',
  'rate limit',
  'forbidden',
];

function sleep(ms) {
  return new Promise(r => setTimeout(r, ms));
}

function isBanned(html, statusCode) {
  if (statusCode === 403 || statusCode === 429 || statusCode === 503) return true;
  const lower = html.toLowerCase();
  return BAN_SIGNATURES.some(sig => lower.includes(sig));
}

async function fetchWithBrowser(url, attempt) {
  const scriptDir = __dirname;
  const storageDir = path.resolve(scriptDir, '../storage/puppeteer');
  const homeDir = path.join(storageDir, 'home');
  const configDir = path.join(storageDir, 'config');
  const cacheDir = path.join(storageDir, 'cache');
  const tmpDir = path.join(storageDir, 'tmp');
  const userDataDir = path.join(storageDir, `user-data-${attempt}`);

  for (const dir of [storageDir, homeDir, configDir, cacheDir, tmpDir, userDataDir]) {
    fs.mkdirSync(dir, { recursive: true });
  }

  const browser = await puppeteer.launch({
    headless: 'new',
    userDataDir,
    env: {
      ...process.env,
      HOME: homeDir,
      XDG_CONFIG_HOME: configDir,
      XDG_CACHE_HOME: cacheDir,
      TMPDIR: tmpDir,
    },
    args: [
      '--no-sandbox',
      '--disable-setuid-sandbox',
      '--disable-dev-shm-usage',
      '--disable-crash-reporter',
      '--disable-breakpad',
      '--disable-features=Crashpad',
      '--disable-blink-features=AutomationControlled',
      '--window-size=1366,900',
      '--disable-infobars',
      '--disable-extensions',
      '--no-first-run',
      '--no-default-browser-check',
    ],
  });

  let page;
  let statusCode = 200;

  try {
    page = await browser.newPage();

    await page.evaluateOnNewDocument(() => {
      Object.defineProperty(navigator, 'webdriver', { get: () => false });

      window.chrome = {
        runtime: { onConnect: null, onMessage: null },
      };

      Object.defineProperty(navigator, 'languages', {
        get: () => ['lv-LV', 'lv', 'en-US', 'en'],
      });

      Object.defineProperty(navigator, 'plugins', {
        get: () => {
          const arr = [1, 2, 3, 4, 5];
          arr.item = (i) => arr[i];
          arr.namedItem = () => null;
          arr.refresh = () => {};
          Object.setPrototypeOf(arr, PluginArray.prototype);
          return arr;
        },
      });

      const originalQuery = window.navigator.permissions.query;
      window.navigator.permissions.query = (parameters) =>
        parameters.name === 'notifications'
          ? Promise.resolve({ state: Notification.permission })
          : originalQuery(parameters);

      Object.defineProperty(navigator, 'hardwareConcurrency', { get: () => 4 });
      Object.defineProperty(navigator, 'deviceMemory', { get: () => 8 });

      Object.defineProperty(navigator, 'userAgentData', {
        get: () => ({
          brands: [
            { brand: 'Google Chrome', version: '131' },
            { brand: 'Chromium', version: '131' },
            { brand: 'Not_A Brand', version: '24' },
          ],
          mobile: false,
          platform: 'Windows',
        }),
      });
    });

    await page.setViewport({ width: 1366, height: 900 });
    await page.setUserAgent(
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
    );

    await page.setExtraHTTPHeaders({
      'Accept-Language': 'lv-LV,lv;q=0.9,en-US;q=0.8,en;q=0.7',
      'Accept': 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
      'Accept-Encoding': 'gzip, deflate, br',
      'Cache-Control': 'no-cache',
      'Pragma': 'no-cache',
      'Sec-Fetch-Dest': 'document',
      'Sec-Fetch-Mode': 'navigate',
      'Sec-Fetch-Site': 'none',
      'Sec-Fetch-User': '?1',
      'Upgrade-Insecure-Requests': '1',
    });

    page.on('response', response => {
      if (response.url() === url || response.request().isNavigationRequest()) {
        statusCode = response.status();
      }
    });

    await sleep(500 + Math.random() * 1000);

    await page.goto(url, {
      waitUntil: 'domcontentloaded',
      timeout: 60000,
    });

    if (page.isClosed()) {
      throw new Error('Page was closed unexpectedly (possible bot detection)');
    }

    try {
      await page.waitForNetworkIdle({ idleTime: 1000, timeout: 10000 });
    } catch {

    }

    if (page.isClosed()) {
      throw new Error('Page was closed after navigation (possible bot detection)');
    }

    await page.evaluate(() => {
      window.scrollBy(0, 200 + Math.random() * 300);
    });

    await sleep(1500 + Math.random() * 1500);

    if (page.isClosed()) {
      throw new Error('Page was closed after scroll (possible bot detection)');
    }

    const html = await page.content();

    if (isBanned(html, statusCode)) {
      const err = new Error(`Bot detection or ban detected (HTTP ${statusCode})`);
      err.isBan = true;
      throw err;
    }

    return html;
  } finally {
    await browser.close();
  }
}

async function main() {
  const url = process.argv[2];

  if (!url) {
    process.stderr.write('Usage: node puppeteer-fetch.js <url>\n');
    process.exit(1);
  }

  let lastError;

  for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    try {
      process.stderr.write(`Attempt ${attempt}/${MAX_RETRIES}: ${url}\n`);
      const html = await fetchWithBrowser(url, attempt);
      process.stdout.write(html);
      return;
    } catch (err) {
      lastError = err;
      process.stderr.write(`Attempt ${attempt} failed: ${err.message}\n`);

      if (attempt < MAX_RETRIES) {
        const delay = RETRY_DELAY_MS * attempt + Math.random() * 2000;
        process.stderr.write(`Retrying in ${Math.round(delay / 1000)}s...\n`);
        await sleep(delay);
      }
    }
  }

  process.stderr.write(`All ${MAX_RETRIES} attempts failed. Last error: ${lastError?.message}\n`);
  process.exit(1);
}

main();