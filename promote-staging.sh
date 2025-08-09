#!/bin/bash
set -e

echo "Promoting DEV to STAGING environment..."

# Confirmation prompt
read -p "Are you sure you want to promote DEV to STAGING? (y/N): " confirm
if [[ $confirm != [yY] && $confirm != [yY][eE][sS] ]]; then
    echo "Promotion cancelled"
    exit 0
fi

# Run promotion script on server
echo "Running promotion script on server..."
ssh root@185.185.126.120 "/home/deploy-scripts/promote-to-staging.sh"

echo "✅ STAGING promotion complete!"
echo "🌐 Check: http://staging.drupalbase.rasmusknabe.dk"