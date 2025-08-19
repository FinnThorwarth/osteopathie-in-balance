#!/bin/bash

# Get the form page first to extract CSRF token
echo "Getting form page..."
RESPONSE=$(curl -s -c cookies.txt http://0.0.0.0:8081/)

# Extract the CSRF token
TOKEN=$(echo "$RESPONSE" | grep -o 'name="contact\[__trustedProperties\]" value="[^"]*"' | sed 's/name="contact\[__trustedProperties\]" value="//;s/"$//' | sed 's/&quot;/"/g')

echo "Submitting form..."
# Submit the form with the token
curl -X POST http://0.0.0.0:8081/ \
  -b cookies.txt \
  -F "contact[__trustedProperties]=$TOKEN" \
  -F "contact[name]=Test User" \
  -F "contact[telefon]=+49 123 456789" \
  -F "contact[email]=test@example.com" \
  -F "contact[nachricht]=Test message from script" \
  -v 2>&1 | grep -E "HTTP|success|error|Vielen Dank" 

# Check Mailhog
echo ""
echo "Checking Mailhog for emails..."
sleep 2
curl -s http://localhost:8025/api/v2/messages | python3 -c "import sys, json; data = json.load(sys.stdin); print(f'Emails in Mailhog: {data[\"total\"]}')"

# Clean up
rm -f cookies.txt