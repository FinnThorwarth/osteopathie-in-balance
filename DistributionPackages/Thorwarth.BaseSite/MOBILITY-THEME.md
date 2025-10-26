# Mobility Theme Documentation

## Overview
This document describes the Mobility brand theme implementation for the miet-mo.de and taxi-pool.de websites. The theme has been integrated into the Tailwind CSS configuration and provides a modern, consistent design system.

## Design Reference
The theme is based on the design mockup located at:
`/Users/finn/Documents/Entwicklung/mobility/neos-app/Design_Desktop.png`

## Color Palette

### Primary Colors

#### Mobility Red (Primary Brand Color)
- **mobility-red**: `#C8102E` - Main brand color used for headers, CTAs, and key elements
- **mobility-red-dark**: `#A00D24` - Hover state and emphasis
- **mobility-red-light**: `#E31E3A` - Lighter variant for backgrounds

**Usage:**
```html
<button class="bg-mobility-red hover:bg-mobility-red-dark text-white">
  Button
</button>
```

#### Navy Blue (Secondary Color)
- **mobility-navy**: `#1E3A5F` - Secondary brand color for sections and accents
- **mobility-navy-dark**: `#162D47` - Darker variant
- **mobility-navy-light**: `#2C5282` - Lighter variant

**Usage:**
```html
<section class="bg-mobility-navy text-white">
  Content
</section>
```

#### Additional Brand Colors
- **mobility-blue**: `#003C71` - Additional blue tone
- **mobility-blue-light**: `#0066B3` - Light blue accent

### Neutral Colors

- **mobility-gray**: `#2B2B2A` - Dark gray for footer and text
- **mobility-gray-dark**: `#1A1A1A` - Darker gray
- **mobility-gray-medium**: `#63635F` - Medium gray
- **mobility-gray-light**: `#B4B1B8` - Light gray
- **mobility-text**: `#4A4A49` - Body text color
- **mobility-text-light**: `#7B7B7B` - Light text

### Accent Colors

- **mobility-accent**: `#FFDD00` - Yellow/Gold accent color (e.g., ADAC partner badge)
- **mobility-white**: `#FFFFFF` - Pure white
- **mobility-bg-light**: `#F5F5F5` - Light background
- **mobility-footer**: `#2B2B2A` - Footer background

## Tailwind Color Palettes

### Primary Palette (Red)
The primary color palette is based on Mobility Red:
```
primary-50  -> #fef2f2 (Lightest)
primary-100 -> #fee2e2
primary-200 -> #fecaca
primary-300 -> #fca5a5
primary-400 -> #f87171
primary-500 -> #C8102E (Main)
primary-600 -> #A00D24
primary-700 -> #8B0A1F
primary-800 -> #7A081B
primary-900 -> #670616 (Darkest)
```

### Secondary Palette (Navy)
The secondary color palette is based on Navy Blue:
```
secondary-50  -> #f0f4f8 (Lightest)
secondary-100 -> #dce5ed
secondary-200 -> #b8cad9
secondary-300 -> #8ca8bf
secondary-400 -> #5f84a2
secondary-500 -> #1E3A5F (Main)
secondary-600 -> #162D47
secondary-700 -> #112439
secondary-800 -> #0d1d2d
secondary-900 -> #0a1621 (Darkest)
```

### Accent Palette (Yellow)
The accent color palette is based on Yellow/Gold:
```
accent-50  -> #fffef0 (Lightest)
accent-100 -> #fffbcc
accent-200 -> #fff899
accent-300 -> #fff566
accent-400 -> #fff233
accent-500 -> #FFDD00 (Main)
accent-600 -> #ccb100
accent-700 -> #998500
accent-800 -> #665900
accent-900 -> #332c00 (Darkest)
```

## Typography

### Font Families

#### Headlines
- **Font**: Ubuntu Sans
- **Usage**: Headings, navigation, buttons
- **Weights**: 100 (thin), 300 (light), 400 (regular), 500 (medium), 600 (semibold)

```html
<h1 class="font-headline font-bold">Headline</h1>
```

#### Body Text
- **Font**: Roboto Slab
- **Usage**: Body text, paragraphs
- **Weights**: 100-900

```html
<p class="font-body font-light">Body text</p>
```

#### Serif (Emphasis)
- **Font**: Roboto Serif
- **Usage**: Italic text, links
- **Weights**: 200-600 (italic variants)

### Heading Styles

The typography configuration includes Mobility-specific heading styles:

- **H1**: Bold, largest size, minimal top margin
- **H2**: Semibold, 2rem top margin
- **H3**: Semibold, 1.5rem top margin
- **H4**: Medium weight, 1rem top margin

**Link Styles:**
- Color: `mobility-red`
- Hover: `mobility-red-dark`
- Underlined by default

## Component Utility Classes

### Buttons

#### Primary Button
```html
<button class="btn-mobility-primary">
  Click Me
</button>
```
- Background: Mobility Red
- Hover: Mobility Red Dark
- Padding: py-3 px-6
- Rounded: Large

#### Secondary Button
```html
<button class="btn-mobility-secondary">
  Click Me
</button>
```
- Background: Mobility Navy
- Hover: Mobility Navy Dark

#### Outline Button
```html
<button class="btn-mobility-outline">
  Click Me
</button>
```
- Border: Mobility Red
- Hover: Fills with Mobility Red

### Layout Components

#### Mobility Header
```html
<header class="mobility-header">
  <!-- Header content -->
</header>
```

#### Mobility Footer
```html
<footer class="mobility-footer">
  <!-- Footer content -->
</footer>
```

#### Mobility Container
```html
<div class="mobility-container">
  <!-- Content with responsive padding -->
</div>
```

### Sections

#### Standard Section
```html
<section class="mobility-section">
  <!-- 16rem padding top/bottom -->
</section>
```

#### Red Section
```html
<section class="mobility-section-red">
  <!-- Red background with white text -->
</section>
```

#### Navy Section
```html
<section class="mobility-section-navy">
  <!-- Navy background with white text -->
</section>
```

#### Light Section
```html
<section class="mobility-section-light">
  <!-- Light gray background -->
</section>
```

### Cards

```html
<div class="mobility-card">
  <!-- Card content with shadow and hover effect -->
</div>
```

### Headings

#### H1
```html
<h1 class="mobility-h1">
  Large Headline
</h1>
```

#### H2
```html
<h2 class="mobility-h2">
  Subheadline
</h2>
```

#### H3
```html
<h3 class="mobility-h3">
  Section Title
</h3>
```

### Text

#### Body Text
```html
<p class="mobility-text-body">
  Body text with optimal readability
</p>
```

#### Links
```html
<a href="#" class="mobility-link">
  Link Text
</a>
```

## CSS Custom Properties

The theme also provides CSS custom properties for use outside of Tailwind:

```css
/* In your custom CSS */
.custom-element {
  color: var(--mobility-red);
  background: var(--mobility-navy);
}
```

Available custom properties:
- `--mobility-red`
- `--mobility-red-dark`
- `--mobility-red-light`
- `--mobility-navy`
- `--mobility-navy-dark`
- `--mobility-blue`
- `--mobility-gray`
- `--mobility-text`
- `--mobility-white`
- `--mobility-accent`

## Design Principles

### 1. Color Usage

**Primary Red (mobility-red)**
- Use for: Headers, primary CTAs, important elements
- Avoid: Large background areas (use sections sparingly)

**Navy Blue (mobility-navy)**
- Use for: Alternating sections, secondary elements
- Works well: With red accents and white text

**Yellow Accent (mobility-accent)**
- Use for: Highlighting, badges, special notices
- Use sparingly: As accent only, not for main content

### 2. White Space
- Generous padding in sections (py-16)
- Consistent container max-width (max-w-7xl)
- Responsive padding adjustments

### 3. Typography Hierarchy
- Clear distinction between heading levels
- Consistent font weights
- Optimal line-height for readability

### 4. Responsive Design
- Mobile-first approach
- Responsive text sizes (text-4xl md:text-5xl lg:text-6xl)
- Adaptive padding (px-4 md:px-6 lg:px-8)

## Migration from Caspary Theme

All legacy Caspary colors remain available for backward compatibility:
- Original colors are prefixed with `caspary-`
- Old primary/secondary/accent palettes renamed to `-legacy`
- Gradually migrate to Mobility colors in new components

Example migration:
```html
<!-- Old -->
<button class="bg-caspary-purple text-white">
  Button
</button>

<!-- New -->
<button class="btn-mobility-primary">
  Button
</button>
```

## Build and Development

After making changes to the Tailwind configuration or CSS files:

```bash
cd DistributionPackages/Thorwarth.BaseSite/
npm run build    # Production build
npm run watch    # Development watch mode
```

## Browser Support

The theme supports all modern browsers:
- Chrome/Edge (latest 2 versions)
- Firefox (latest 2 versions)
- Safari (latest 2 versions)
- iOS Safari (latest 2 versions)

## Examples

### Hero Section
```html
<section class="mobility-section-red">
  <div class="mobility-container">
    <h1 class="mobility-h1 text-white mb-6">
      Mobilität, die zu Ihnen passt!
    </h1>
    <p class="mobility-text-body text-white mb-8">
      Finden Sie das passende Fahrzeug für Ihre Bedürfnisse
    </p>
    <button class="btn-mobility-primary">
      Jetzt buchen
    </button>
  </div>
</section>
```

### Service Cards
```html
<section class="mobility-section-light">
  <div class="mobility-container">
    <h2 class="mobility-h2 text-center mb-12">
      Unsere Services
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
      <div class="mobility-card">
        <img src="..." alt="Service" class="w-full h-48 object-cover">
        <div class="p-6">
          <h3 class="mobility-h3 text-lg mb-3">Spezialfahrzeuge</h3>
          <p class="mobility-text-body">
            Für besondere Anforderungen
          </p>
          <a href="#" class="mobility-link mt-4 inline-block">
            Mehr erfahren →
          </a>
        </div>
      </div>
      <!-- More cards... -->
    </div>
  </div>
</section>
```

### Contact Section
```html
<section class="mobility-section-navy">
  <div class="mobility-container">
    <h2 class="mobility-h2 text-white mb-6">
      Wir sind für Sie da - schnell und unkompliziert
    </h2>
    <p class="mobility-text-body text-white mb-8">
      Kontaktieren Sie uns für Fragen und Buchungen
    </p>
    <div class="flex flex-col md:flex-row gap-4">
      <a href="tel:04269276967" class="btn-mobility-outline text-white border-white hover:bg-white hover:text-mobility-navy">
        📞 04269 - 27 96 67
      </a>
      <a href="mailto:info@miet-pool.de" class="btn-mobility-outline text-white border-white hover:bg-white hover:text-mobility-navy">
        ✉️ info@miet-pool.de
      </a>
    </div>
  </div>
</section>
```

## Support

For questions or issues with the Mobility theme, refer to:
- Main documentation: `/CLAUDE.md`
- Tailwind config: `DistributionPackages/Thorwarth.BaseSite/tailwind.config.js`
- CSS styles: `DistributionPackages/Thorwarth.BaseSite/Resources/Private/Styles/app.css`
