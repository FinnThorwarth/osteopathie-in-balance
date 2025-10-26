#!/bin/bash
#
# Create a fresh Neos site
# Usage: ./create-fresh-site.sh [site-name] [node-name]
#
# Example: ./create-fresh-site.sh "My Site" mysite
#

set -e

# Default values
SITE_NAME="${1:-BaseSite}"
NODE_NAME="${2:-basesite}"
PACKAGE_KEY="Mobility.Site"
NODE_TYPE="Thorwarth.BaseSite:Document.Homepage"

echo "================================================"
echo "Creating fresh Neos site: $SITE_NAME"
echo "Node name: $NODE_NAME"
echo "================================================"
echo ""

# Step 1: Delete all personal workspaces
echo "Step 1: Cleaning up personal workspaces..."
WORKSPACES=$(docker compose exec -T neos ./flow workspace:list --quiet 2>/dev/null | grep -E "PERSONAL" | awk '{print $1}' || true)
if [ -n "$WORKSPACES" ]; then
    for workspace in $WORKSPACES; do
        echo "  - Deleting workspace: $workspace"
        docker compose exec -T neos ./flow workspace:delete "$workspace" --force
    done
else
    echo "  - No personal workspaces found"
fi
echo ""

# Step 2: Prune all site data
echo "Step 2: Pruning all site data..."
yes y | docker compose exec -T neos ./flow site:pruneall 2>&1 | grep -v "^>" || true
echo ""

# Step 3: Set up content repository
echo "Step 3: Setting up content repository..."
docker compose exec -T neos ./flow cr:setup
echo ""

# Step 4: Create new site
echo "Step 4: Creating site '$SITE_NAME'..."
docker compose exec -T neos ./flow site:create --node-name "$NODE_NAME" "$SITE_NAME" "$PACKAGE_KEY" "$NODE_TYPE"
echo ""

# Step 5: Flush caches
echo "Step 5: Flushing caches..."
docker compose exec -T neos ./flow neos.flow:cache:flush --force > /dev/null
docker compose exec -T neos ./flow neos.flow:cache:warmup > /dev/null
echo ""

# Step 6: Verify
echo "Step 6: Verifying site..."
docker compose exec -T neos ./flow site:list
echo ""

echo "================================================"
echo "✅ Done! Site created successfully."
echo ""
echo "Next steps:"
echo "1. Clear your browser cache (Ctrl+Shift+Del / Cmd+Shift+Del)"
echo "2. Go to http://localhost:8081/neos"
echo "3. Log in and start editing"
echo "================================================"
