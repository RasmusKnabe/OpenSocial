#!/bin/bash
set -e

echo "Starting deployment to DEV environment..."

# Export Drupal configuration locally
echo "Exporting Drupal configuration..."
if ddev exec "drush config:export -y"; then
    echo "✓ Configuration exported successfully"
else
    echo "⚠ Config export failed - checking if site is accessible..."
    ddev exec "drush status" || echo "Site may not be accessible"
fi

# Commit changes to git
echo "Committing changes to git..."
git add .
if git commit -m "Config export for DEV deployment - $(date '+%Y-%m-%d %H:%M')"; then
    echo "✓ Changes committed to git"
else
    echo "ℹ No changes to commit"
fi

# Create .deployignore if it doesn't exist
if [ ! -f ".deployignore" ]; then
    echo "Creating .deployignore file..."
    cat > .deployignore << EOF
.git/
.ddev/
node_modules/
*.log
web/sites/default/files/
.DS_Store
vendor/
deploy-*.sh
deploy-strategy.md
session-notes.md
project-summary*.md
docs/
EOF
fi

# Deploy to DEV server
echo "Deploying files to DEV server..."
rsync -avz --exclude-from=.deployignore ./ root@185.185.126.120:/home/DrupalBase-DEV/

# Fix file permissions after deployment
echo "Fixing file permissions..."
ssh root@185.185.126.120 "chown -R apache:apache /home/DrupalBase-DEV/web/ && find /home/DrupalBase-DEV/web -name '.htaccess' -exec chmod 644 {} \;"

# Run update script on server
echo "Running update script on DEV server..."
ssh root@185.185.126.120 "/home/deploy-scripts/dev-update.sh"

echo "✅ DEV deployment complete!"
echo "🌐 Check: http://dev.drupalbase.rasmusknabe.dk"