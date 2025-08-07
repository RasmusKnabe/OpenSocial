# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

OpenSocial Drupal distribution project with local development and live server deployment.

**Live Server**: 185.185.126.120 (root@ssh)
**Git Repository**: https://github.com/RasmusKnabe

## Project Structure

- `web/` - Drupal OpenSocial installation directory
- `docs/` - Project documentation
- `session-notes.md` - Cross-session continuity tracking

## Custom Workflow Commands

When the user types these commands, execute the corresponding actions:

### `/check`
Mark a milestone or checkpoint in the current session. Add a checkmark entry to session-notes.md under current session with timestamp and brief description.

### `/wrap`
1. Update session-notes.md with a comprehensive summary of the current session
2. Focus on completed milestones and checkpoints
3. Update relevant documentation in the `docs/` folder
4. Commit changes if appropriate

### `/recap`
1. Read and summarize session-notes.md to understand previous work
2. Read current documentation in `docs/` folder
3. Provide a brief overview of project status and next steps

### `/sync`
Update project documentation in the `docs/` folder independently of session wrapping.

## Development Environment

- **Local**: Devilbox environment
- **Deployment**: SSH to 185.185.126.120 as root
- **Framework**: Drupal with OpenSocial distribution