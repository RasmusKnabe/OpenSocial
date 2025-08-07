# Deployment Strategy

## Overview

3-tier deployment setup: Local Development → Staging → Production

## Environments

### Local Development (Devilbox)
- **Location**: `/Users/rasmusl.knabe/Desktop/devilbox/data/www/OpenSocial`
- **URL**: `http://opensocial.loc` (eller lignende)
- **Database**: Local MySQL via Devilbox
- **Purpose**: Development og testing

### Production Server
- **Server**: 185.185.126.120
- **User**: root
- **Access**: SSH key authentication
- **Web Root**: `/var/www/opensocial` (eller lignende)
- **Database**: MySQL/MariaDB på serveren

## Deployment Methods

### 1. Git-Based Deployment (Recommended)

```bash
# På live server
git clone https://github.com/RasmusKnabe/OpenSocial.git /var/www/opensocial
cd /var/www/opensocial
composer install --no-dev --optimize-autoloader
```

### 2. Manual SSH Deployment

```bash
# Fra lokal maskine
rsync -avz --exclude-from='.gitignore' ./ root@185.185.126.120:/var/www/opensocial/
```

### 3. Automated Deployment Script

Vi kan oprette deployment scripts der:
- Synkroniserer kode via Git
- Kører Composer install
- Opdaterer database (Drush)
- Clearer caches
- Backup før deployment

## Database Deployment

### Initial Setup
1. Export local database
2. Import på live server
3. Opdater `settings.php` med live database credentials

### Updates
- Brug Drush til database updates
- Configuration sync via Drupal configuration management

## File Management

### Drupal Files Directory
- Local: Included in development
- Live: Persistent storage, ikke overskrevet ved deployment

### Deployment Workflow

1. **Development**: Arbejd lokalt i Devilbox
2. **Commit**: Git commit changes
3. **Push**: Push til GitHub repository
4. **Deploy**: Pull på live server + run deployment script
5. **Verify**: Test live site functionality

## Security Considerations

- SSH key authentication
- Environment-specific settings files
- Database credentials via environment variables eller secure settings files
- File permissions på live server (www-data ownership)