const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
  let browser;

  try {
    console.log('Starting Puppeteer...');

    browser = await puppeteer.launch({
      headless: 'new',
      args: ['--no-sandbox', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();

    // Add error listener
    page.on('pageerror', error => {
      console.log('Page error:', error.message);
    });

    page.on('error', error => {
      console.log('Error:', error.message);
    });

    // Set viewport to desktop size
    await page.setViewport({
      width: 1920,
      height: 1080,
      deviceScaleFactor: 1
    });

    console.log('Navigating to http://0.0.0.0:8081/...');

    await page.goto('http://0.0.0.0:8081/', {
      waitUntil: 'domcontentloaded',
      timeout: 30000
    });

    console.log('Page loaded successfully');

    // Wait a bit for any JS to load
    await page.waitForTimeout(2000);

    // Take full page screenshot
    await page.screenshot({
      path: 'current-design.png',
      fullPage: true
    });

    console.log('Screenshot saved as current-design.png');

    // Extract computed colors from the page
    const colors = await page.evaluate(() => {
      const results = {};

      // Get colors from various elements
      const elements = {
        body: document.body,
        header: document.querySelector('header'),
        nav: document.querySelector('nav'),
        footer: document.querySelector('footer'),
        buttons: document.querySelectorAll('button, .btn, [class*="button"]'),
        links: document.querySelectorAll('a'),
        headings: document.querySelectorAll('h1, h2, h3, h4, h5, h6')
      };

      Object.entries(elements).forEach(([key, element]) => {
        if (element) {
          if (NodeList.prototype.isPrototypeOf(element)) {
            // Handle NodeList
            element.forEach((el, index) => {
              const styles = window.getComputedStyle(el);
              results[`${key}_${index}`] = {
                backgroundColor: styles.backgroundColor,
                color: styles.color,
                fontFamily: styles.fontFamily
              };
            });
          } else {
            // Handle single element
            const styles = window.getComputedStyle(element);
            results[key] = {
              backgroundColor: styles.backgroundColor,
              color: styles.color,
              fontFamily: styles.fontFamily
            };
          }
        }
      });

      return results;
    });

    // Save color analysis
    fs.writeFileSync('current-colors.json', JSON.stringify(colors, null, 2));
    console.log('Color analysis saved as current-colors.json');

  } catch (error) {
    console.error('Error during screenshot:', error.message);
    console.error('Stack trace:', error.stack);
  } finally {
    if (browser) {
      await browser.close();
    }
    console.log('Done!');
  }
})().catch(error => {
  console.error('Unhandled error:', error);
  process.exit(1);
});
