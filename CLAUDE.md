# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview
This is a Neos CMS 9.0 project for the Apfelbluete website built with Vue.js 3, Tailwind CSS, and Vite.

## Development Commands

### Local Development (without Docker)
```bash
# Frontend development (in DistributionPackages/Thorwarth.BaseSite/)
cd DistributionPackages/Thorwarth.BaseSite/
npm install
npm run dev      # Start Vite dev server with HMR
npm run build    # Build frontend assets for production
npm run watch    # Watch and rebuild on changes

# Neos Flow commands
./flow help                              # Show all available commands
./flow cache:flush                       # Clear all caches
./flow cr:setup                         # Set up content repository
./flow cr:prune                         # Prune unused content repository events
./flow doctrine:migrate                 # Run database migrations
./flow user:create --roles Administrator # Create admin user
./flow resource:publish                 # Publish resources to public directory
```

### Docker Development
```bash
make up          # Start containers
make down        # Stop containers
make shell       # Access web container
make flow cmd=   # Run Flow commands
make composer cmd= # Run Composer commands
make db-shell    # Access database
```

## Architecture

### Project Structure
- **DistributionPackages/** - Contains site-specific packages
  - **Smartgrund.Site** - Main site package
  - **Thorwarth.BaseSite** - Base theme and component library with Vue.js integration
- **Packages/** - Neos packages and libraries (managed by Composer)
- **Web/** - Public webroot (auto-generated)
- **Data/** - Runtime data, logs, and persistent storage
- **Configuration/** - Neos/Flow configuration files

### Frontend Stack
- **Vue.js 3** with Composition API for interactive components (Search, Navigation, Accordion)
- **Tailwind CSS** with custom Caspary color scheme and typography
- **Vite** for fast HMR development and optimized production builds
- Components are mounted in Fusion templates via data attributes

### Key Technologies
- **Neos CMS 9.0** - Enterprise content management
- **Neos Flow Framework** - PHP application framework
- **Neos.Fusion** - Template rendering engine
- **MariaDB 10.11** - Database
- **Vue.js 3** - Frontend framework for interactive components
- **Vite** - Frontend build tool with HMR support

### Vue Component Integration
Vue components in `Resources/Private/JavaScript/components/` are mounted to DOM elements with corresponding data attributes (e.g., `data-vue-search`, `data-vue-navigation`). The main.js file handles component initialization.

## Database
- Default connection uses MariaDB on port 13307
- Database name: caspary_neos
- Credentials configured in Configuration/Development/Docker/Settings.Database.yaml

## Testing
```bash
# Run Puppeteer tests (contact form testing)
npm test
```

## Build System
Frontend assets are built with Vite and include:
- Automatic Fusion file watching with hot reload
- Vue single-file component support
- Tailwind CSS with custom configuration
- Critical CSS optimization with Critters
- Manifest generation for cache busting
