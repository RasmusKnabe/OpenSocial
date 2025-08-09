#!/bin/bash
set -e

echo "Promoting STAGING to PRODUCTION environment..."

# Confirmation prompt
echo "⚠️  WARNING: This will deploy to PRODUCTION!"
read -p "Are you sure you want to promote STAGING to PRODUCTION? (y/N): " confirm
if [[ $confirm != [yY] && $confirm != [yY][eE][sS] ]]; then
    echo "Production promotion cancelled"
    exit 0
fi

# Second confirmation
read -p "This will affect the live site. Type 'DEPLOY' to confirm: " deploy_confirm
if [[ $deploy_confirm != "DEPLOY" ]]; then
    echo "Production promotion cancelled"
    exit 0
fi

# Run promotion script on server
echo "Running production promotion script on server..."
ssh root@185.185.126.120 "/home/deploy-scripts/promote-to-prod.sh"

echo "✅ PRODUCTION promotion complete!"
echo "🌐 Check: http://prod.drupalbase.rasmusknabe.dk"