# Contact Form Debug Environment

This debug environment helps you test and troubleshoot the contact form module without sending real emails.

## Features

- **Mailhog Integration**: Catches all emails sent by the form
- **Debug Mode**: Shows additional information in forms
- **Test Data**: Auto-fill forms with sample data
- **Form Validation Testing**: Test all validation rules
- **Email Preview**: See exactly what emails will look like
- **Submission Logging**: Track all form submissions

## Quick Start

### 1. Start the Debug Environment

```bash
cd neos-app
./debug-form.sh start
```

This will:
- Start Mailhog mail catcher
- Clear Flow cache to activate debug settings
- Display access URLs

### 2. Access the Tools

- **Mailhog Web UI**: http://localhost:8025
  - View all caught emails
  - Inspect email headers and content
  - Download emails as .eml files

- **Test Form Page**: Open `/Users/finn/Documents/Entwicklung/caspary/neos-app/DistributionPackages/Caspary.Site/Resources/Private/Templates/Test/ContactFormTest.html` in your browser
  - Interactive form testing
  - Real-time validation
  - Debug console
  - Email preview

- **Neos Backend**: http://localhost:8081/neos
  - Add ContactForm content element
  - Test in real page context

## Using the Debug Environment

### Test the Contact Form

1. **In Neos Backend**:
   - Create a new page or edit existing
   - Add "Kontaktformular" content element
   - Configure recipient email (will be overridden to test@localhost in debug mode)
   - Save and view the page

2. **Using Test HTML Page**:
   - Open ContactFormTest.html in browser
   - Use "Fill Test Data" button for quick testing
   - Submit form and check Mailhog for caught emails

### Debug Features

When debug mode is active, forms will show:
- Current recipient email
- Form namespace
- Node path
- Validation errors with field names
- Submission data in JSON format

### Commands

```bash
# Start debug environment
./debug-form.sh start

# Stop debug environment
./debug-form.sh stop

# View Mailhog logs
./debug-form.sh logs

# Clear all test emails
./debug-form.sh clear

# Check status
./debug-form.sh status
```

## Configuration Files

### Debug Settings
- **Location**: `/Configuration/Development/Settings.Forms.Debug.yaml`
- **Purpose**: Configures debug mode, test recipient, and logging

### Debug Fusion Template
- **Location**: `/Resources/Private/Fusion/Content/ContactForm.Debug.fusion`
- **Purpose**: Adds debug output to forms

### Test HTML Page
- **Location**: `/Resources/Private/Templates/Test/ContactFormTest.html`
- **Purpose**: Standalone testing interface

## Testing Scenarios

### 1. Basic Submission Test
- Fill all required fields
- Submit form
- Check Mailhog for email
- Verify success message

### 2. Validation Testing
- Submit empty form → Should show validation errors
- Submit invalid email → Should show email validation error
- Submit without required fields → Should highlight missing fields

### 3. Special Characters Test
- Use special characters in name: "Müller & Söhne"
- Use umlauts in message: "Über uns äöü"
- Verify correct encoding in email

### 4. Long Message Test
- Submit very long message (1000+ characters)
- Verify complete message in email
- Check formatting preservation

### 5. Phone Number Formats
- Test different formats: "+49 123 456789", "0123-456789", "0123/456789"
- Verify all formats accepted

## Troubleshooting

### Emails Not Appearing in Mailhog
1. Check Mailhog is running: `./debug-form.sh status`
2. Verify SMTP settings in debug configuration
3. Clear Flow cache: `./flow flow:cache:flush`

### Form Not Showing Debug Info
1. Ensure you're in Development context
2. Check debug configuration is loaded
3. Restart Neos after configuration changes

### Validation Not Working
1. Check JavaScript console for errors
2. Verify required attributes on form fields
3. Test in different browsers

## Production Deployment

⚠️ **Important**: Debug features should NEVER be deployed to production!

Before deployment:
1. Remove or disable `Settings.Forms.Debug.yaml`
2. Don't include `ContactForm.Debug.fusion`
3. Remove test HTML files
4. Ensure real SMTP settings are configured
5. Test with production email settings

## Email Configuration for Production

In production, configure real SMTP in `Configuration/Production/Settings.yaml`:

```yaml
Neos:
  Flow:
    mailer:
      dsn: 'smtp://username:password@smtp.server.com:587?encryption=tls'
```

## Support

For issues or questions about the contact form:
1. Check debug console output
2. Review Mailhog caught emails
3. Check Flow system logs: `Data/Logs/System_Development.log`
4. Check form submission logs: `Data/Logs/ContactForm.log`