# Mobility Neos CMS Project Constitution

<!--
Sync Impact Report:
Version: 1.0.0 (Initial Constitution)
Created: 2025-11-10
Modified Principles: N/A (Initial creation)
Added Sections: All core sections
Removed Sections: None
Templates Status:
  ✅ plan-template.md - Aligned with constitution principles
  ✅ spec-template.md - Aligned with user story requirements
  ✅ tasks-template.md - Aligned with atomic design principles
Follow-up TODOs: None
-->

## Core Principles

### I. Atomic Design Methodology

**MUST** follow atomic design principles for all UI components and Neos Fusion templates:

- **Atoms**: Basic building blocks (buttons, inputs, labels, icons) as standalone Fusion prototypes in `Resources/Private/Fusion/Component/Atom/`
- **Molecules**: Simple combinations of atoms (search bars, form fields) in `Resources/Private/Fusion/Component/Molecule/`
- **Organisms**: Complex UI sections combining molecules and atoms (headers, navigation, forms) in `Resources/Private/Fusion/Component/Organism/`
- **Templates**: Page-level layouts combining organisms in `Resources/Private/Fusion/Template/`
- **Pages**: Specific page implementations using templates in `Resources/Private/Fusion/Page/`

Each component MUST be:
- **Self-contained**: No external dependencies except explicitly declared props/prototypes
- **Reusable**: Usable across both miet-mo.de and taxi-pool.de websites
- **Documented**: Include inline Fusion comments describing purpose and props
- **Tested**: Manually verified across mobile, tablet, and desktop viewports

**Rationale**: Atomic design ensures consistency, maintainability, and scalability across both websites while preventing component duplication and technical debt.

### II. Neos Fusion First

**MUST** implement all templating logic in Neos Fusion, not in PHP controllers or custom ViewHelpers:

- Fusion prototypes for all components (atoms through organisms)
- AFX syntax for readable HTML-like templates
- Fusion processors for data transformation and business logic
- EEL expressions for simple conditional rendering and data access
- FlowQuery for content queries within Fusion

**AVOID**:
- Fluid templates (Neos uses Fusion/AFX)
- Inline PHP in templates
- Complex logic in PHP controllers that should be in Fusion processors
- Custom ViewHelpers unless absolutely necessary for performance

**Rationale**: Fusion is Neos's templating engine designed for component-based architecture. Keeping logic in Fusion maintains the separation of concerns and leverages Neos's built-in content editing capabilities.

### III. Vue.js for Interactivity Only

**MUST** use Vue.js 3 Composition API exclusively for client-side interactivity, NOT for content rendering:

- Interactive components ONLY: search, navigation toggles, accordions, forms with dynamic behavior
- Mount points via data attributes (e.g., `data-vue-search`, `data-vue-navigation`)
- Components in `Resources/Private/JavaScript/components/`
- Props passed via data attributes for server-side rendered initial state
- Vite for development with HMR

**MUST NOT**:
- Use Vue.js for static content rendering (use Fusion/AFX instead)
- Duplicate Fusion components as Vue components
- Implement Vue-based routing (use Neos routing)
- Use Vue.js for SEO-critical content rendering

**Rationale**: Neos excels at server-side rendering and content management. Vue.js should enhance user experience with interactivity while maintaining SEO, accessibility, and editor-friendly content management.

### IV. Responsive & Accessible by Default (NON-NEGOTIABLE)

**MUST** ensure every component meets these standards BEFORE marking complete:

- **Responsive**: Mobile-first Tailwind CSS, tested on 375px (mobile), 768px (tablet), 1440px (desktop)
- **Accessible**: WCAG 2.1 AA compliance
  - Semantic HTML5 elements
  - Proper heading hierarchy (h1→h2→h3, no skips)
  - ARIA labels for interactive elements
  - Keyboard navigation support (tab order, focus states)
  - Color contrast ratios ≥4.5:1 for text
  - Alt text for images via Neos image properties
- **Performance**:
  - Images via Neos image service with responsive srcset
  - Vue.js lazy loading for non-critical components
  - Critical CSS optimization via Vite/Critters

**Rationale**: Both accessibility and responsiveness are legal requirements (WCAG) and business requirements (conversion optimization). Retrofitting is expensive; building correctly from the start is mandatory.

### V. Multi-Site Architecture

**MUST** design all components to support both websites (miet-mo.de and taxi-pool.de):

- Shared component library in `Thorwarth.BaseSite` package
- Site-specific configuration via Neos site nodes and Fusion context
- Tailwind CSS custom color schemes per site (Caspary colors configurable)
- Content dimensions for German language (future-proofing for multi-language)
- No hardcoded site-specific logic in shared components

**Configuration Approach**:
- Site colors/branding via Fusion context variables
- Site-specific content via Neos content repository
- Site-specific behavior via Fusion conditions based on site node
- Shared JavaScript components with configurable props

**Rationale**: Maintaining two separate codebases is unsustainable. Shared components with site-specific configuration enable consistent quality while allowing brand differentiation.

### VI. Content Editor First

**MUST** prioritize content editor experience in all component design:

- Inline editing enabled for all text content via Neos.Neos:ContentComponent
- Clear component naming in Neos inspector (German labels for editors)
- Sensible defaults for all component properties
- Preview in backend matches frontend (Fusion context consistency)
- No technical jargon in editor-facing labels
- Inspector groups for organizing complex component properties

**AVOID**:
- Components requiring developers to update after editor changes
- Hidden configuration only accessible via YAML
- Breaking inline editing with JavaScript overlays
- Complex property dependencies confusing editors

**Rationale**: Content editors are the primary users of the CMS. Components that frustrate editors lead to support overhead and content quality issues.

### VII. Git Workflow & Deployment

**MUST** follow this workflow for all changes:

- **Branching**: Feature branches from `main` (e.g., `feature/hero-component`, `fix/navigation-mobile`)
- **Commits**: Conventional Commits format (e.g., `feat(Atom): add Button component`, `fix(Navigation): mobile menu toggle`)
- **Testing**: Manual testing across devices BEFORE commit
- **Pull Requests**: Required for all changes, self-review mandatory
- **Deployment**:
  - Frontend: `npm run build` in `DistributionPackages/Thorwarth.BaseSite/`
  - Backend: `./flow cache:flush && ./flow resource:publish`
  - Database: `./flow doctrine:migrate` for schema changes

**Rationale**: Structured workflow prevents breaking changes and maintains code quality throughout the relaunch project.

## Component Organization

### Fusion Component Structure

All Fusion components MUST follow this structure:

```
Resources/Private/Fusion/Component/
├── Atom/
│   ├── Button/
│   │   ├── Button.fusion          # Prototype definition
│   │   └── Button.module.css      # Optional scoped styles (if not pure Tailwind)
│   ├── Icon/
│   ├── Input/
│   └── Label/
├── Molecule/
│   ├── SearchBar/
│   ├── FormField/
│   └── Card/
├── Organism/
│   ├── Header/
│   ├── Navigation/
│   ├── Footer/
│   └── ContactForm/
├── Template/
│   ├── DefaultPage.fusion
│   └── LandingPage.fusion
└── Page/
    ├── HomePage.fusion
    └── ServicePage.fusion
```

### Vue.js Component Structure

Vue.js components MUST be organized by feature:

```
Resources/Private/JavaScript/
├── components/
│   ├── Search.vue              # Search functionality
│   ├── Navigation.vue          # Mobile navigation toggle
│   ├── Accordion.vue           # Expandable content sections
│   └── ContactForm.vue         # Dynamic form validation
├── composables/
│   └── useFormValidation.js   # Reusable composition functions
└── main.js                     # Component initialization
```

## Development Workflow

### Component Development Checklist

For EVERY new component, the developer MUST:

1. **Design Review**: Verify design exists (Figma/mockup) before implementation
2. **Atomic Level**: Determine correct atomic level (Atom/Molecule/Organism)
3. **Props Definition**: Define all configurable properties with defaults
4. **Fusion Implementation**:
   - Create Fusion prototype file
   - Use AFX syntax for template
   - Add Fusion comments documenting props
5. **Styling**: Apply Tailwind CSS classes (utility-first)
6. **Responsiveness Test**: Test on mobile (375px), tablet (768px), desktop (1440px)
7. **Accessibility Test**:
   - Keyboard navigation
   - Screen reader labels
   - Color contrast
8. **Vue.js Enhancement** (if needed):
   - Create Vue component for interactivity ONLY
   - Add mount point data attribute to Fusion template
   - Pass server-rendered state via props
9. **Editor Experience**: Configure Neos inspector properties with German labels
10. **Documentation**: Add inline Fusion comments and usage examples

### Performance Standards

**MUST** meet these performance targets:

- **Lighthouse Score**: ≥90 Performance, ≥95 Accessibility, ≥90 Best Practices, ≥90 SEO
- **First Contentful Paint (FCP)**: ≤1.5s on 3G
- **Largest Contentful Paint (LCP)**: ≤2.5s
- **Cumulative Layout Shift (CLS)**: ≤0.1
- **Time to Interactive (TTI)**: ≤3.5s

**Optimization Requirements**:
- Responsive images with Neos image service
- Lazy loading for below-the-fold images
- Vue.js code splitting for non-critical components
- Critical CSS extraction via Vite
- Minification and compression in production

## Content Standards

### German Language Requirements

All content MUST adhere to:

- **Spelling**: New German spelling rules (neue deutsche Rechtschreibung)
- **Tone**: Professional yet approachable ("Sie" form for B2B, friendly for B2C)
- **Clarity**: Short sentences, clear call-to-actions
- **SEO**: Keyword-optimized headlines and meta descriptions
- **Accessibility**: Plain language, no unexplained jargon

### SEO Requirements

**MUST** implement for all pages:

- Unique, descriptive page titles (≤60 characters)
- Meta descriptions (≤155 characters)
- Structured data (Schema.org) via Neos SEO package
- Semantic HTML heading hierarchy
- Alt text for all images
- Clean URL structure via Neos URL handling
- XML sitemap generation

## Governance

This constitution supersedes all other development practices and guidelines. All code changes, pull requests, and architectural decisions MUST be validated against these principles.

### Amendment Process

1. Proposed amendments MUST be documented in pull request description
2. Amendment requires approval from project lead
3. Version MUST be incremented:
   - **MAJOR**: Principle removed or fundamentally changed
   - **MINOR**: New principle added or existing principle expanded
   - **PATCH**: Clarification, wording improvement, or typo fix
4. All dependent templates and documentation MUST be updated before merge
5. Team MUST be notified of constitution changes

### Compliance Review

**Mandatory Reviews**:

- **Pull Request Review**: All PRs MUST verify constitutional compliance in review checklist
- **Component Review**: New components reviewed against atomic design principles
- **Accessibility Audit**: Monthly WCAG compliance checks
- **Performance Audit**: Monthly Lighthouse score reviews

**Complexity Justification**:

Any deviation from principles MUST be justified in pull request with:
- Why standard approach insufficient
- Simpler alternatives considered and rejected
- Plan to refactor to compliant approach (if temporary deviation)

### Development Guidance

For runtime development guidance specific to this project, refer to:
- `/Users/finn/Documents/Entwicklung/mobility/CLAUDE.md` - Project overview and goals
- `/Users/finn/Documents/Entwicklung/mobility/neos-app/CLAUDE.md` - Technical setup and commands

**Version**: 1.0.0 | **Ratified**: 2025-11-10 | **Last Amended**: 2025-11-10
