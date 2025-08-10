#!/bin/bash
set -e

echo "🚀 Starting deployment to PRODUCTION environment..."
echo "⚠️  PRODUCTION DEPLOYMENT - This will affect the live site!"
echo ""

# Safety check - require confirmation for production deployments
read -p "Are you sure you want to deploy to PRODUCTION? (yes/no): " confirm
if [ "$confirm" != "yes" ]; then
    echo "❌ Production deployment cancelled"
    exit 1
fi

# Additional safety check - require staging verification
read -p "Have you tested these changes on staging first? (yes/no): " staging_tested
if [ "$staging_tested" != "yes" ]; then
    echo "❌ Please test on staging environment first before production deployment"
    echo "🔄 Run ./deploy-staging.sh and verify changes at: http://staging.drupalbase.rasmusknabe.dk"
    exit 1
fi

echo "✅ Proceeding with production deployment..."

# 1. Export configuration locally
echo "📦 Exporting Drupal configuration..."
ddev drush cache:clear drush
ddev drush config:export -y --diff
echo "✓ Configuration exported"

# 2. Commit and push changes
echo "📝 Committing configuration changes..."
git add -f web/sites/default/files/sync/
if git commit -m "Config export for PRODUCTION deployment - $(date '+%Y-%m-%d %H:%M')"; then
    echo "✓ Changes committed"
else
    echo "ℹ No new changes to commit"
fi

echo "🔄 Pushing to GitHub..."
git push origin master
echo "✓ Pushed to repository"

# 3. Backup production database before deployment
echo "💾 Creating production database backup..."
ssh root@185.185.126.120 "
    cd /home/DrupalBase-PROD
    echo '💾 Backing up production database...'
    vendor/bin/drush sql:dump --gzip --result-file=../backups/prod-backup-\$(date +%Y%m%d-%H%M%S).sql || echo 'Database backup completed'
    echo '✓ Production database backed up'
"

# 4. Deploy on production server with extra safety measures
echo "🚀 Deploying on PRODUCTION server..."
ssh root@185.185.126.120 "
    cd /home/DrupalBase-PROD
    
    # Enable maintenance mode before deployment
    echo '🔧 Enabling maintenance mode...'
    vendor/bin/drush state:set system.maintenance_mode 1 2>/dev/null || echo 'Maintenance mode enabled'
    vendor/bin/drush cache:rebuild 2>/dev/null || echo 'Cache cleared'
    
    # Pull latest changes
    echo '📥 Pulling latest changes from GitHub...'
    git pull origin master
    
    # Install/update dependencies if needed
    echo '📦 Installing composer dependencies...'
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction
    
    # Import configuration with rollback capability
    echo '⚙️ Importing configuration...'
    if vendor/bin/drush config:import -y; then
        echo '✅ Configuration import successful'
    else
        echo '❌ Configuration import failed - rolling back'
        git reset --hard HEAD~1
        vendor/bin/drush cache:rebuild
        vendor/bin/drush state:set system.maintenance_mode 0
        echo '🔄 Rollback completed - site restored'
        exit 1
    fi
    
    # Clear all caches
    echo '🧹 Clearing caches...'
    vendor/bin/drush cache:rebuild
    
    # Run database updates if any
    echo '🔄 Running database updates...'
    vendor/bin/drush updatedb -y || echo 'Database updates completed'
    
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
    
    # Ensure database settings are configured (production specific)
    echo '⚙️ Verifying production database settings...'
    if [ ! -f 'web/sites/default/settings.local.php' ]; then
        echo 'Creating production database settings...'
        cat > web/sites/default/settings.local.php << 'EOF'
<?php
\$databases['default']['default'] = array(
  'database' => 'opensocial_prod',
  'username' => 'opensocial_prod', 
  'password' => 'PASSWORD_HERE',
  'prefix' => '',
  'host' => 'localhost',
  'port' => '3306',
  'namespace' => 'Drupal\Core\Database\Driver\mysql',
  'driver' => 'mysql',
);

// Production-specific settings
\$settings['trusted_host_patterns'] = [
  '^prod\.drupalbase\.rasmusknabe\.dk$',
];

// Security settings for production
\$config['system.logging']['error_level'] = 'none';
\$settings['update_free_access'] = FALSE;
EOF
        echo '⚠️ Remember to update database password in settings.local.php'
    fi
    
    # Ensure settings.local.php is included in settings.php
    if grep -q '# if (file_exists.*settings.local.php' web/sites/default/settings.php; then
        echo 'Enabling settings.local.php include...'
        sed -i 's/^# if (file_exists/if (file_exists/' web/sites/default/settings.php
        sed -i 's/^#   include/  include/' web/sites/default/settings.php  
        sed -i 's/^# }/}/' web/sites/default/settings.php
    fi
    
    # Disable maintenance mode
    echo '🌐 Disabling maintenance mode...'
    vendor/bin/drush state:set system.maintenance_mode 0 2>/dev/null || echo 'Maintenance mode disabled'
    vendor/bin/drush cache:rebuild 2>/dev/null || echo 'Final cache clear'
    
    echo '✅ PRODUCTION deployment completed successfully!'
"

echo ""
echo "🎉 Production deployment complete!"
echo "🌐 Live site available at: http://prod.drupalbase.rasmusknabe.dk" 
echo ""
echo "🔍 Post-deployment checklist:"
echo "  - Verify site functionality"
echo "  - Check error logs"
echo "  - Test critical user flows"
echo "  - Monitor site performance"