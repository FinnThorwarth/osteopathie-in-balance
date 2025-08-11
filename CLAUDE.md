# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is the Caspary Bestattungen website relaunch project using Neos CMS 9. The repository contains both design assets and the Neos CMS implementation with modern frontend technologies.

## Technology Stack

- **CMS**: Neos CMS 9
- **CSS Framework**: Tailwind CSS 3.4
- **JavaScript Framework**: Vue.js 3
- **Build Tool**: Vite 5
- **PHP Version**: 8.2
- **Database**: MySQL/MariaDB

## Project Structure

```
caspary/
├── design/                         # Design assets
│   ├── 250706-Caspary-Website-desktop.pdf
│   ├── Caspary-Burger-Menue.svg
│   └── mobil-Menü-*.jpg
└── neos-app/                      # Neos CMS application
    ├── DistributionPackages/
    │   └── Caspary.Site/          # Custom site package
    │       ├── Resources/Private/  # Frontend sources
    │       └── Resources/Public/   # Built assets
    ├── flow                       # Neos CLI tool
    └── Configuration/
        └── Settings.yaml          # Database & app config
```

## Development Commands

### Docker Setup (Recommended)
```bash
cd neos-app

# Initial setup
make setup

# Start containers
make up

# Stop containers
make down

# View logs
make logs

# Access web container
make shell

# Run Flow commands
make flow cmd="help"
make flow cmd="user:list"

# Run Composer
make composer cmd="install"

# Access database
make db-shell
```

### Manual Setup (Without Docker)
```bash
cd neos-app

# Database setup (run once)
./flow setup

# Create/update database schema
./flow doctrine:migrate

# Clear caches
./flow flow:cache:flush

# Create admin user
./flow user:create --roles Administrator admin@caspary.de

# Start development server
./flow server:run
```

### Frontend (Tailwind/Vue)
```bash
cd neos-app/DistributionPackages/Caspary.Site

# Install dependencies
yarn install

# Development with hot reload
yarn dev

# Build for production
yarn build

# Watch mode
yarn watch
```

## IMPORTANT RULES
- Textedits should be done inline, not in the sidebar.
- Textareas should be able to use some styling, like bold, italic, underline, etc.
- Images should be cropped to the aspect ratio defined in the design.
- Images should always have an alt text and a title.
- All labels should be in german.
- DONT do comments like this: {/* Autor-Header */}
- Headings should not be formatted by wysiwyg.
- Alle NodeTypes sollen in dem Verzeichnis '/Users/finn/Documents/Entwicklung/caspary/neos-app/DistributionPackages/Caspary.Site/NodeTypes' abgelegt werden! Bevor ein neues angelegt wird, muss geprüft werden, ob es bereits welche gibt.

## Design Guidelines

1. **Colors**: Extract from design PDF - currently placeholder colors in tailwind.config.js
2. **Mobile Menu**: Structure defined in design/mobil-Menü-*.jpg files
3. **Typography**: Use appropriate fonts from design (currently using Inter as placeholder)
4. **Responsive Design**: Mobile-first approach following the design mockups

## Key Implementation Details

1. **Vue.js Integration**: Components are mounted to specific DOM elements rather than SPA approach
2. **Tailwind CSS**: Configured to scan Fusion templates and Vue components
3. **Vite Build**: Outputs to Resources/Public with manifest for cache busting
4. **Fusion Templates**: Located in Resources/Private/Fusion/


## Access URLs

### With Docker
- **Neos Frontend**: http://localhost:8081
- **Neos Backend**: http://localhost:8081/neos
- **Vite Dev Server**: http://localhost:5173
- **Admin Login**: admin / admin@caspary.de

### Debugging URL
http://127.0.0.1:8081

