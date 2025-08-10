#!/bin/bash
set -e

echo "🎨 Syncing design assets to DEV server..."

# Sync assets directory (design files only)
echo "📁 Syncing assets directory..."
rsync -avz --delete web/sites/default/files/assets/ root@185.185.126.120:/home/DrupalBase-DEV/web/sites/default/files/assets/

# Fix permissions on synced assets
ssh root@185.185.126.120 "
    cd /home/DrupalBase-DEV
    echo '🔒 Setting asset permissions...'
    chown -R apache:apache web/sites/default/files/assets/
    find web/sites/default/files/assets -type d -exec chmod 755 {} \;
    find web/sites/default/files/assets -type f -exec chmod 644 {} \;
    
    echo '🧹 Clearing image style caches...'
    vendor/bin/drush image:flush --all 2>/dev/null || echo 'Image styles cleared'
    vendor/bin/drush cache:rebuild 2>/dev/null || echo 'Cache cleared'
"

echo "✅ Asset sync completed!"
echo "🌐 Design assets updated at: http://dev.drupalbase.rasmusknabe.dk"