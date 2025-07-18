#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASSWORD" -e "SELECT 1" &> /dev/null; do
  echo "MySQL is unavailable - sleeping"
  sleep 2
done

echo "MySQL is up - executing commands"

# Set correct permissions
chown -R www-data:www-data /app/Data
chmod -R 777 /app/Data
chmod -R 777 /app/Web/_Resources

# Run Neos setup if not already done
if [ ! -f "/app/Data/.setup-done" ]; then
    echo "Running Neos setup..."
    cd /app
    
    # Run database migrations
    ./flow doctrine:migrate --quiet
    
    # Import site if not exists
    ./flow site:list | grep -q "caspary-site" || ./flow site:create caspary-site Caspary.Site Caspary.Site:Document.Homepage
    
    # Create admin user if not exists
    ./flow user:list | grep -q "admin" || ./flow user:create --roles Administrator admin admin@caspary.de Admin User
    
    # Mark setup as done
    touch /app/Data/.setup-done
    
    echo "Neos setup completed!"
fi

# Start Apache
exec apache2-foreground