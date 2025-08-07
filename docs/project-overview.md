# OpenSocial Project Overview

## Projekt mål
Installation og deployment af OpenSocial Drupal distribution med lokal udvikling og live server setup.

## Teknisk stack
- **Framework**: Drupal med OpenSocial distribution
- **Package Manager**: Composer
- **Lokal miljø**: Devilbox
- **Live Server**: 185.185.126.120 (SSH som root)
- **Version Control**: Git + GitHub (https://github.com/RasmusKnabe)

## Projekt struktur
```
OpenSocial/
├── web/                    # Drupal installation
├── docs/                   # Projekt dokumentation
│   ├── installation-guide.md
│   ├── deployment-strategy.md
│   └── project-overview.md
├── session-notes.md        # Cross-session tracking
├── CLAUDE.md              # Claude Code guidance
└── .gitignore             # Git ignore rules
```

## Workflow kommandoer
- `/check` - Markér milepæl med timestamp
- `/wrap` - Opdater session notes + dokumentation  
- `/recap` - Læs tidligere arbejde ved session start
- `/sync` - Opdater dokumentation uafhængigt

## Development workflow
1. **Lokal udvikling** - Devilbox environment
2. **Git commits** - Version control af ændringer
3. **GitHub push** - Remote repository sync
4. **Deployment** - SSH til live server med automatiseret proces

## Næste milepæle
- [ ] GitHub repository setup og remote connection
- [ ] OpenSocial Composer installation
- [ ] Devilbox URL konfiguration
- [ ] Live server environment test
- [ ] Database setup (lokal + live)
- [ ] Deployment workflow implementering