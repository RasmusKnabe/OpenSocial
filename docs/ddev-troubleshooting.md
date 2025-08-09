# DDEV Troubleshooting Guide

## Container Timeout Issues

### Problem
DDEV containers timeout during startup with error:
```
health check timed out after 2m0s: web container failed
```

### Løsninger (i prioriteret rækkefølge)

#### 1. Docker Restart
```bash
# Stop alle DDEV projekter
ddev poweroff

# Restart Docker Desktop app
# Eller via CLI:
sudo systemctl restart docker  # Linux
# På macOS: Restart Docker Desktop app

# Start projekt igen
ddev start
```

#### 2. System Cleanup
```bash
# Stop og ryd op
ddev stop
docker system prune -f
docker volume prune -f

# Start igen
ddev start
```

#### 3. Memory og Resources
- Øg Docker Desktop memory til minimum 4GB
- Check disk space (Docker kræver mindst 10GB fri)

#### 4. Container Rebuild
```bash
# Genbyg containers fra scratch
ddev delete -O
ddev start
```

#### 5. Debug Commands
```bash
# Tjek container status
ddev logs -s web
docker logs ddev-opensocial-web
docker inspect ddev-opensocial-web
```

## Mutagen Sync Issues

Hvis Mutagen sync tager lang tid:
```bash
# Stop Mutagen
ddev mutagen reset

# Restart projekt
ddev restart
```

## Performance Tips

- Eksluder projekt folder fra antivirus scanning
- Brug SSD disk for Docker volumes
- Luk unødvendige applikationer under development

## Specific Issues Resolved

### phpstatus Endpoint Missing
**Problem**: Health check fails with "phpstatus:FAILED"
**Solution**: Add phpstatus location to nginx configuration:

```nginx
# PHP-FPM status endpoint for DDEV health check
location = /phpstatus {
    access_log off;
    fastcgi_pass unix:/run/php-fpm.sock;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    include fastcgi_params;
}
```

### CSS Aggregation Issues with PHP 8.2
**Problem**: CSS files don't load due to aggregation errors
**Solutions**:
1. Disable CSS/JS aggregation in database:
```sql
UPDATE config SET data = REPLACE(data, 's:10:"preprocess";b:1;', 's:10:"preprocess";b:0;') WHERE name = 'system.performance';
```

2. Clear CSS cache directory:
```bash
rm -rf web/sites/default/files/css/*
rm -rf web/sites/default/files/js/*
```

### PHP 8.2 Deprecation Warnings
**Problem**: Group module shows "static" deprecation warnings
**Solution**: Add to settings.php:
```php
// Hide PHP 8.2 deprecation warnings in development
if (getenv('IS_DDEV_PROJECT') == 'true') {
  error_reporting(E_ALL & ~E_DEPRECATED);
}
```