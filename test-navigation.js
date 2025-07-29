const puppeteer = require('puppeteer');

(async () => {
  const browser = await puppeteer.launch({
    headless: false,
    devtools: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox']
  });
  
  try {
    const page = await browser.newPage();
    
    // Enable console logging
    page.on('console', msg => console.log('PAGE LOG:', msg.text()));
    page.on('pageerror', error => console.log('PAGE ERROR:', error.message));
    
    console.log('Navigating to homepage...');
    await page.goto('http://localhost:8081', { 
      waitUntil: 'networkidle2',
      timeout: 30000 
    });
    
    // Wait for navigation element
    console.log('Waiting for navigation element...');
    await page.waitForSelector('#navigation-vue', { timeout: 10000 });
    
    // Get the navigation data from script tag
    const navigationData = await page.evaluate(() => {
      const dataEl = document.getElementById('navigation-data');
      return dataEl ? dataEl.textContent : null;
    });
    
    console.log('Navigation data:', navigationData);
    
    // Check if Vue component is mounted
    const vueComponentExists = await page.evaluate(() => {
      const navEl = document.getElementById('navigation-vue');
      return navEl && navEl._vueInstance ? true : false;
    });
    
    console.log('Vue component mounted:', vueComponentExists);
    
    // Click the menu button to open navigation
    console.log('Looking for menu button...');
    const menuButton = await page.$('button img[alt="Menü"]');
    if (menuButton) {
      const buttonElement = await menuButton.evaluateHandle(el => el.parentElement);
      await buttonElement.click();
      console.log('Clicked menu button');
      
      // Wait a bit for menu to open
      await new Promise(resolve => setTimeout(resolve, 1000));
      
      // Check if menu is visible
      const menuVisible = await page.evaluate(() => {
        const menuWrapper = document.querySelector('.menu-wrapper');
        return menuWrapper ? window.getComputedStyle(menuWrapper).display !== 'none' : false;
      });
      
      console.log('Menu visible:', menuVisible);
      
      // Get menu items
      const menuItems = await page.evaluate(() => {
        const items = [];
        const menuHeaders = document.querySelectorAll('.menu-wrapper h3 a, .menu-wrapper h3 span');
        menuHeaders.forEach(header => {
          items.push(header.textContent.trim());
        });
        return items;
      });
      
      console.log('Menu items found:', menuItems);
    } else {
      console.log('Menu button not found');
    }
    
    // Take a screenshot
    await page.screenshot({ path: 'navigation-test.png', fullPage: true });
    console.log('Screenshot saved as navigation-test.png');
    
  } catch (error) {
    console.error('Error:', error);
  } finally {
    // Keep browser open for inspection
    console.log('Press Ctrl+C to close the browser...');
  }
})();