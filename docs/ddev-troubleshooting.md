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