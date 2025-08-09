# OpenSocial Deployment Strategy

## Environment Overview

| Environment | Domain | Path | Database | Purpose |
|-------------|--------|------|----------|---------|
| **DEV** | dev.drupalbase.rasmusknabe.dk | /home/DrupalBase-DEV/ | drupalbase_dev | Development testing |
| **Staging** | staging.drupalbase.rasmusknabe.dk | /home/DrupalBase-Staging/ | drupalbase_staging | Pre-production testing |
| **Prod** | prod.drupalbase.rasmusknabe.dk | /home/DrupalBase-Prod/ | drupalbase_prod | Live production |

## Deployment Flow

```
Local DDEV → DEV → Staging → Prod
     ↓         ↓       ↓        ↓
Config Export → Test → Test → Deploy
```

## Deployment Scripts

### 1. Local → DEV (deploy-dev.sh)
```bash
#!/bin/bash
# Export config locally
ddev exec "drush config:export -y"
git add . && git commit -m "Config export for DEV deployment"

# Deploy to DEV
rsync -avz --exclude-from=.deployignore ./ root@185.185.126.120:/home/DrupalBase-DEV/
ssh root@185.185.126.120 "cd /home/DrupalBase-DEV && ./deploy-scripts/dev-update.sh"
```

### 2. DEV → Staging (promote-staging.sh)
```bash
#!/bin/bash
# Copy DEV to Staging
ssh root@185.185.126.120 "cd /home && ./deploy-scripts/promote-to-staging.sh"
```

### 3. Staging → Prod (promote-prod.sh)
```bash
#!/bin/bash
# Copy Staging to Prod (with backup)
ssh root@185.185.126.120 "cd /home && ./deploy-scripts/promote-to-prod.sh"
```

## Server-side Scripts (on 185.185.126.120)

### /home/deploy-scripts/dev-update.sh
```bash
#!/bin/bash
cd /home/DrupalBase-DEV
composer install --no-dev
drush config:import -y
drush cache:rebuild
drush updatedb -y
echo "DEV deployment complete"
```

### /home/deploy-scripts/promote-to-staging.sh
```bash
#!/bin/bash
# Backup current staging
tar -czf /backups/staging-$(date +%Y%m%d-%H%M).tar.gz /home/DrupalBase-Staging

# Copy DEV to Staging
rsync -av --exclude=web/sites/default/files/ /home/DrupalBase-DEV/ /home/DrupalBase-Staging/

# Update staging database settings
cd /home/DrupalBase-Staging
# Update database connection in settings.php for staging DB
drush config:import -y
drush cache:rebuild
echo "Staging promotion complete"
```

### /home/deploy-scripts/promote-to-prod.sh
```bash
#!/bin/bash
# Backup production
mysqldump drupalbase_prod > /backups/prod-db-$(date +%Y%m%d-%H%M).sql
tar -czf /backups/prod-$(date +%Y%m%d-%H%M).tar.gz /home/DrupalBase-Prod

# Copy staging to prod
rsync -av --exclude=web/sites/default/files/ /home/DrupalBase-Staging/ /home/DrupalBase-Prod/

# Update prod database settings
cd /home/DrupalBase-Prod
# Update database connection in settings.php for prod DB
drush config:import -y
drush cache:rebuild
drush updatedb -y
echo "Production promotion complete"
```

## Configuration Management

### Config Export/Import Flow
1. **Local**: `drush config:export` før deployment
2. **DEV**: `drush config:import` efter kode deployment
3. **Staging**: Config importeres når promoted fra DEV
4. **Prod**: Config importeres når promoted fra Staging

### Files to Exclude (.deployignore)
```
.git/
.ddev/
node_modules/
*.log
web/sites/default/files/
vendor/
.DS_Store
```

## Database Strategy

### Development
- Database kan overskrives frit
- Bruges til feature testing

### Staging  
- Kopi af prod data for realistisk testing
- Config changes testes her først

### Production
- Live data - kun config import, aldrig database overwrite
- Automatisk backup før hver deployment

## Safety Checks

### Pre-deployment Validation
- [ ] Drush status check
- [ ] Database connection test  
- [ ] File permissions verification
- [ ] Config validation

### Post-deployment Verification
- [ ] Site accessible via URL
- [ ] Login funktionalitet
- [ ] Critical features test
- [ ] Error log check

## Rollback Strategy

### Quick Rollback
```bash
# Restore from backup
tar -xzf /backups/prod-YYYYMMDD-HHMM.tar.gz -C /
mysql drupalbase_prod < /backups/prod-db-YYYYMMDD-HHMM.sql
```

### Git-based Rollback
```bash
# Rollback to previous commit
git checkout HEAD~1
./deploy-scripts/dev-update.sh
```