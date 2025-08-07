# OpenSocial Installation Guide

## System Requirements

### PHP Requirements
- PHP 8.1+ (recommended)
- PHP EXIF extension (recommended for image auto-rotation)
- WebP extension (required for OpenSocial 11.5.x+)

### Database
- MySQL/MariaDB or PostgreSQL
- Standard Drupal database requirements

### Server Environment
- **Local**: Devilbox environment
- **Live**: 185.185.126.120 (SSH access as root)

## Installation Methods

### 1. Quick Installation (Recommended)

```bash
# Create project using Composer
composer create-project goalgorilla/social_template:dev-master opensocial --no-interaction

# Navigate to project
cd opensocial

# The Drupal root will be in the 'html' directory
```

### 2. Local Devilbox Installation

Since we're using Devilbox, we need to:

1. Install OpenSocial in the `web/` directory
2. Configure Devilbox to serve from the correct directory
3. Set up database connection

### 3. Live Server Deployment

- SSH deployment to 185.185.126.120
- Apache/Nginx configuration
- Database setup
- File permissions

## Post-Installation Steps

1. Run Drupal installation via browser or Drush
2. Rebuild node access permissions
3. Configure site settings
4. Set up deployment workflow

## Directory Structure

```
OpenSocial/
├── web/              # Drupal installation (html/ from template)
├── vendor/           # Composer dependencies
├── composer.json     # Dependencies management
├── docs/            # Project documentation
└── session-notes.md # Session tracking
```