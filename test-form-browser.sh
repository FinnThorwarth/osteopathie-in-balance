#!/bin/bash

echo "Opening browser to test the contact form..."
echo ""
echo "Instructions:"
echo "1. Go to: http://0.0.0.0:8081/"
echo "2. Fill in the contact form with:"
echo "   - Name: Test User"
echo "   - Email: test@example.com"  
echo "   - Message: Test message"
echo "3. Submit the form"
echo "4. Check for success message on the page"
echo "5. Check Mailhog at: http://localhost:8025"
echo ""
echo "Opening URLs in browser..."

# Open the website
open http://0.0.0.0:8081/

# Wait a moment
sleep 2

# Open Mailhog
open http://localhost:8025

echo ""
echo "Browser windows opened. Please test the form manually."
echo ""
echo "After testing, check Mailhog for the email."