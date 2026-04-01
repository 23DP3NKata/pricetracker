#!/usr/bin/env node

const puppeteer = require('puppeteer');

async function main() {
  const url = process.argv[2];

  if (!url) {
    process.stderr.write('Usage: node puppeteer-fetch.js <url>\n');
    process.exit(1);
  }

  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
  });

  try {
    const page = await browser.newPage();
    await page.setUserAgent(
      'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36'
    );

    await page.setExtraHTTPHeaders({
      'Accept-Language': 'lv-LV,lv;q=0.9,en-US;q=0.8,en;q=0.7',
    });

    await page.goto(url, {
      waitUntil: 'networkidle2',
      timeout: 45000,
    });

    const html = await page.content();
    process.stdout.write(html);
  } finally {
    await browser.close();
  }
}

main().catch((error) => {
  process.stderr.write(`${error?.message || String(error)}\n`);
  process.exit(1);
});
