#!/bin/bash

# Contact Form Debug Environment Script
# This script sets up and runs the debug environment for testing the contact form

echo "========================================="
echo "Contact Form Debug Environment"
echo "========================================="
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}Error: Docker is not running!${NC}"
    echo "Please start Docker Desktop first."
    exit 1
fi

# Function to start services
start_debug_env() {
    echo -e "${GREEN}Starting debug environment...${NC}"
    
    # Start Mailhog
    echo "Starting Mailhog mail catcher..."
    docker compose -f docker-compose.debug.yml up -d mailhog
    
    # Wait for Mailhog to be ready
    echo "Waiting for Mailhog to be ready..."
    sleep 3
    
    # Check if Mailhog is running
    if curl -s http://localhost:8025/api/v2/messages > /dev/null; then
        echo -e "${GREEN}✓ Mailhog is running${NC}"
    else
        echo -e "${RED}✗ Mailhog failed to start${NC}"
        exit 1
    fi
    
    # Clear Flow cache for debug settings to take effect
    echo "Clearing Flow cache..."
    cd /Users/finn/Documents/Entwicklung/caspary/neos-app
    ./flow flow:cache:flush
    
    echo ""
    echo -e "${GREEN}Debug environment is ready!${NC}"
    echo ""
    echo "========================================="
    echo "Access URLs:"
    echo "========================================="
    echo "• Mailhog Web UI: http://localhost:8025"
    echo "• Test Form Page: http://localhost:8081/test-contact-form"
    echo "• Neos Backend: http://localhost:8081/neos"
    echo ""
    echo "========================================="
    echo "Debug Features:"
    echo "========================================="
    echo "✓ All emails are caught by Mailhog"
    echo "✓ Debug information displayed in forms"
    echo "✓ Form submissions logged"
    echo "✓ Test data auto-fill available"
    echo ""
    echo "Press Ctrl+C to stop the debug environment"
}

# Function to stop services
stop_debug_env() {
    echo ""
    echo -e "${YELLOW}Stopping debug environment...${NC}"
    docker compose -f docker-compose.debug.yml down
    echo -e "${GREEN}Debug environment stopped.${NC}"
}

# Function to show logs
show_logs() {
    echo -e "${GREEN}Showing Mailhog logs...${NC}"
    docker compose -f docker-compose.debug.yml logs -f mailhog
}

# Function to clear mailhog messages
clear_emails() {
    echo -e "${YELLOW}Clearing all test emails...${NC}"
    curl -X DELETE http://localhost:8025/api/v1/messages
    echo -e "${GREEN}All test emails cleared.${NC}"
}

# Trap Ctrl+C to stop services gracefully
trap stop_debug_env INT

# Main menu
case "${1:-start}" in
    start)
        start_debug_env
        # Keep script running
        while true; do
            sleep 1
        done
        ;;
    stop)
        stop_debug_env
        ;;
    logs)
        show_logs
        ;;
    clear)
        clear_emails
        ;;
    status)
        if curl -s http://localhost:8025/api/v2/messages > /dev/null 2>&1; then
            echo -e "${GREEN}✓ Mailhog is running${NC}"
            MESSAGES=$(curl -s http://localhost:8025/api/v2/messages | grep -o '"total":[0-9]*' | grep -o '[0-9]*')
            echo "Total test emails: $MESSAGES"
        else
            echo -e "${RED}✗ Mailhog is not running${NC}"
        fi
        ;;
    *)
        echo "Usage: $0 {start|stop|logs|clear|status}"
        echo ""
        echo "Commands:"
        echo "  start  - Start the debug environment (default)"
        echo "  stop   - Stop the debug environment"
        echo "  logs   - Show Mailhog logs"
        echo "  clear  - Clear all test emails"
        echo "  status - Check if services are running"
        exit 1
        ;;
esac