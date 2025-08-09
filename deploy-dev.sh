#!/bin/bash
set -e

echo "Starting deployment to DEV environment..."

# Export Drupal configuration locally
echo "Exporting Drupal configuration..."
ddev exec "drush config:export -y" || echo "Config export failed - continuing without"

# Commit changes to git
echo "Committing changes to git..."
git add .
git commit -m "Config export for DEV deployment - $(date '+%Y-%m-%d %H:%M')" || echo "No changes to commit"

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

# Run update script on server
echo "Running update script on DEV server..."
ssh root@185.185.126.120 "/home/deploy-scripts/dev-update.sh"

echo "✅ DEV deployment complete!"
echo "🌐 Check: http://dev.drupalbase.rasmusknabe.dk"