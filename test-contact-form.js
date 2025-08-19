const puppeteer = require('puppeteer');

async function testContactForm() {
    console.log('Starting Contact Form Test...');
    
    const browser = await puppeteer.launch({
        headless: false, // Show browser for debugging
        args: [
            '--no-sandbox', 
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage'
        ],
        defaultViewport: null
    });
    
    try {
        const page = await browser.newPage();
        
        // Navigate to the page
        console.log('Navigating to http://0.0.0.0:8081/');
        await page.goto('http://0.0.0.0:8081/', { 
            waitUntil: 'networkidle2',
            timeout: 30000 
        });
        
        // Wait for the contact form to be present
        console.log('Waiting for contact form...');
        await page.waitForSelector('.contact-form', { timeout: 10000 });
        
        // Fill in the form fields
        console.log('Filling out form...');
        await page.type('input[name="contact[name]"]', 'Test User');
        await page.type('input[name="contact[telefon]"]', '+49 123 456789');
        await page.type('input[name="contact[email]"]', 'test@example.com');
        await page.type('textarea[name="contact[nachricht]"]', 'Dies ist eine Testnachricht von Puppeteer.');
        
        // Take a screenshot before submission
        await page.screenshot({ path: 'form-before-submit.png', fullPage: true });
        console.log('Screenshot saved: form-before-submit.png');
        
        // Submit the form
        console.log('Submitting form...');
        await page.click('button[type="submit"]');
        
        // Wait for response (either success message or error)
        await page.waitForTimeout(3000);
        
        // Take a screenshot after submission
        await page.screenshot({ path: 'form-after-submit.png', fullPage: true });
        console.log('Screenshot saved: form-after-submit.png');
        
        // Check for success message
        const successMessage = await page.$('.alert-success, .success-message, [class*="success"]');
        if (successMessage) {
            console.log('✅ Form submitted successfully!');
            const messageText = await page.evaluate(el => el.textContent, successMessage);
            console.log('Success message:', messageText);
        }
        
        // Check for error messages
        const errorMessage = await page.$('.alert-danger, .error-message, [class*="error"]');
        if (errorMessage) {
            console.log('❌ Form submission error detected');
            const errorText = await page.evaluate(el => el.textContent, errorMessage);
            console.log('Error message:', errorText);
        }
        
        // Check Mailhog for the email
        console.log('\nChecking Mailhog for caught email...');
        const mailhogPage = await browser.newPage();
        await mailhogPage.goto('http://localhost:8025/', { waitUntil: 'networkidle2' });
        
        await mailhogPage.waitForTimeout(2000);
        
        // Check if there are any emails
        const emails = await mailhogPage.$$('.messages .message');
        if (emails.length > 0) {
            console.log(`✅ Found ${emails.length} email(s) in Mailhog`);
            
            // Click on the first email to view it
            await emails[0].click();
            await mailhogPage.waitForTimeout(1000);
            
            // Take screenshot of Mailhog
            await mailhogPage.screenshot({ path: 'mailhog-email.png', fullPage: true });
            console.log('Screenshot saved: mailhog-email.png');
        } else {
            console.log('❌ No emails found in Mailhog');
        }
        
        console.log('\n✅ Test completed successfully!');
        
    } catch (error) {
        console.error('❌ Test failed:', error.message);
        
        // Take error screenshot
        const page = (await browser.pages())[0];
        await page.screenshot({ path: 'error-screenshot.png', fullPage: true });
        console.log('Error screenshot saved: error-screenshot.png');
        
        throw error;
    } finally {
        await browser.close();
    }
}

// Run the test
testContactForm().catch(console.error);