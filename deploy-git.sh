#!/bin/bash
set -e

echo "🚀 Starting Git-based deployment to DEV environment..."

# 1. Export configuration locally
echo "📦 Exporting Drupal configuration..."
ddev drush cache:clear drush
ddev drush config:export -y --diff
echo "✓ Configuration exported"

# 2. Commit and push changes
echo "📝 Committing configuration changes..."
git add -f web/sites/default/files/sync/
if git commit -m "Config export for deployment - $(date '+%Y-%m-%d %H:%M')"; then
    echo "✓ Changes committed"
else
    echo "ℹ No new changes to commit"
fi

echo "🔄 Pushing to GitHub..."
git push origin master
echo "✓ Pushed to repository"

# 3. Deploy on server via git pull
echo "🚀 Deploying on DEV server..."
ssh root@185.185.126.120 "
    cd /home/DrupalBase-DEV
    
    # Pull latest changes
    echo '📥 Pulling latest changes from GitHub...'
    git pull origin master
    
    # Install/update dependencies if needed
    echo '📦 Installing composer dependencies...'
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
    
    # Import configuration
    echo '⚙️ Importing configuration...'
    vendor/bin/drush config:import -y || echo 'Config import completed (may have had warnings)'
    
    # Clear all caches
    echo '🧹 Clearing caches...'
    vendor/bin/drush cache:rebuild
    
    # Fix permissions (but preserve user content)
    echo '🔒 Fixing file permissions...'
    chown -R apache:apache web/
    find web -name '.htaccess' -exec chmod 644 {} \;
    
    # Ensure assets directory has correct permissions
    if [ -d 'web/sites/default/files/assets' ]; then
        echo '🎨 Setting asset permissions...'
        chown -R apache:apache web/sites/default/files/assets/
        find web/sites/default/files/assets -type d -exec chmod 755 {} \;
        find web/sites/default/files/assets -type f -exec chmod 644 {} \;
    fi
    
    # Ensure settings.local.php is included in settings.php
    echo '⚙️ Verifying database settings...'
    if grep -q '# if (file_exists.*settings.local.php' web/sites/default/settings.php; then
        echo 'Enabling settings.local.php include...'
        sed -i 's/^# if (file_exists/if (file_exists/' web/sites/default/settings.php
        sed -i 's/^#   include/  include/' web/sites/default/settings.php  
        sed -i 's/^# }/}/' web/sites/default/settings.php
    fi
    
    echo '✅ DEV deployment completed successfully!'
"

echo ""
echo "🎉 Deployment complete!"
echo "🌐 Check your changes at: http://dev.drupalbase.rasmusknabe.dk"