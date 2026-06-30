---
name: Industrial Precision
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#584237'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#8c7165'
  outline-variant: '#e0c0b1'
  surface-tint: '#9e4300'
  primary: '#9e4300'
  on-primary: '#ffffff'
  primary-container: '#ee6c12'
  on-primary-container: '#4d1d00'
  inverse-primary: '#ffb691'
  secondary: '#545f73'
  on-secondary: '#ffffff'
  secondary-container: '#d5e0f8'
  on-secondary-container: '#586377'
  tertiary: '#00629f'
  on-tertiary: '#ffffff'
  tertiary-container: '#0098f3'
  on-tertiary-container: '#002d4e'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdbcb'
  primary-fixed-dim: '#ffb691'
  on-primary-fixed: '#341100'
  on-primary-fixed-variant: '#783100'
  secondary-fixed: '#d8e3fb'
  secondary-fixed-dim: '#bcc7de'
  on-secondary-fixed: '#111c2d'
  on-secondary-fixed-variant: '#3c475a'
  tertiary-fixed: '#d0e4ff'
  tertiary-fixed-dim: '#9bcbff'
  on-tertiary-fixed: '#001d34'
  on-tertiary-fixed-variant: '#004a79'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-price:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Inter
    fontSize: 32px
    fontWeight: '700'
    lineHeight: 40px
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  body-lg:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-xl:
    fontFamily: Inter
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 24px
  label-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  headline-lg-mobile:
    fontFamily: Inter
    fontSize: 28px
    fontWeight: '700'
    lineHeight: 36px
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  base: 8px
  gutter: 24px
  margin-mobile: 16px
  margin-desktop: 32px
  touch-target-min: 48px
---

## Brand & Style

This design system is engineered for the high-velocity environment of building material retail. The brand personality is **authoritative, industrial, and hyper-efficient**, prioritizing speed of task completion over decorative flair. 

The aesthetic blends **Modern Corporate** reliability with **High-Contrast** utility. It utilizes a "Utility-First" approach where every visual element serves a functional purpose, ensuring that staff can process transactions, manage inventory, and handle heavy-duty logistics without cognitive friction. The interface evokes a sense of structural integrity, reflecting the very materials being sold.

The target emotional response is **confidence and clarity**. In a busy store, the UI acts as a stable foundation, using generous whitespace to prevent visual clutter and clear, high-contrast boundaries to define interactive zones.

## Colors

The palette is anchored by a high-visibility **Industrial Orange**, chosen for its immediate recognizability in busy environments. This primary color is reserved strictly for primary actions, critical alerts, and brand touchpoints.

- **Primary (Industrial Orange):** Used for "Complete Transaction," "Add to Cart," and active selection states.
- **Secondary (Deep Slate):** Used for navigation, headers, and structural elements to provide a grounded, professional feel.
- **Neutral (Cool Grays):** The background utilizes a very light gray (`#F8FAFC`) rather than pure white to reduce eye strain during long shifts, while maintaining high contrast against text.
- **Functional Colors:** High-saturation greens and reds are used for stock availability and error states, ensuring they are legible even from a distance or under harsh overhead lighting.

## Typography

This design system uses **Inter** exclusively for its exceptional legibility and neutral, systematic character. The type scale is intentionally oversized to accommodate the varied lighting and viewing distances common in retail warehouses.

- **Large Price Displays:** A dedicated `display-price` style is used for total amounts to ensure zero ambiguity during checkout.
- **Labeling:** All labels use a higher weight and occasional uppercase styling to distinguish data headers from the data itself.
- **Accessibility:** The base body size starts at 16px, with 18px being the preferred standard for transactional lists to ensure readability for all users.

## Layout & Spacing

The layout follows a **Fixed Grid** philosophy on desktop to maintain a consistent scan-path for power users, switching to a **Fluid Grid** on mobile/tablet devices.

- **Grid System:** A 12-column grid is used for the main dashboard. Transaction sidebars are fixed at a 400px width on desktop to keep the checkout button in a predictable location.
- **Rhythm:** An 8px linear scale governs all spacing.
- **Touch Targets:** Given the "tactile" requirement, all interactive elements maintain a minimum height of 48px to prevent accidental taps, especially when users are wearing work gloves or moving quickly.
- **Safe Zones:** Generous margins (32px) on desktop prevent the UI from feeling cramped and help the user focus on the central task.

## Elevation & Depth

To maintain high contrast and an "industrial" feel, this design system avoids soft ambient shadows. Instead, it uses **Bold Borders** and **Tonal Layers** to define hierarchy.

- **Outlines:** All cards and containers use a 1px or 2px solid border (`#E2E8F0`).
- **Interactive Depth:** Buttons use a subtle "pressed" effect (0.5px Y-offset) rather than complex shadows to simulate a physical, tactile switch.
- **Layering:** The primary canvas is light, while "Active" areas like search bars or selected list items use a slight tint change (Secondary 50) to indicate focus.

## Shapes

The shape language is **Soft (0.25rem)**. This provides a subtle modern touch without losing the structural, "hard-edged" feeling of building materials. 

- **Containers:** Dashboard tiles and product cards use `rounded-lg` (0.5rem) to distinguish them as discrete units of information.
- **Interactive Elements:** Buttons and input fields use the base `0.25rem` radius to maintain a professional, crisp appearance.
- **Inputs:** Checkboxes and radio buttons maintain sharp corners or minimal rounding to reinforce the industrial aesthetic.

## Components

### Buttons
- **Primary:** Solid Industrial Orange background with White text. Bold weight. Min-height 56px for main actions.
- **Secondary:** Transparent background with 2px Slate border.
- **Tactile State:** On hover, the background darkens by 10%. On press, the element shifts 1px downward to provide physical feedback.

### Cards & Dashboard Tiles
- Cards feature a 1px border.
- Header sections of cards use a subtle gray background (`#F1F5F9`) to separate title information from content.
- Dashboard tiles for categories (e.g., "Lumber", "Hardware") feature large, 32px icons and centered `label-xl` text.

### Inputs & Labels
- Labels are always positioned *above* the input field, never as placeholders, to ensure visibility during data entry.
- Active inputs feature a 2px Industrial Orange border.
- Error states use a high-contrast Red border with an accompanying helper icon.

### Lists & Tables
- Inventory lists use "Zebra Striping" (alternating light gray rows) to help the eye track across wide data sets.
- Row height is a minimum of 64px to ensure tap-ability for line items.

### Quantity Toggles
- Stepper components (Plus/Minus) are oversized, occupying a minimum of 120px width to allow for fast quantity adjustments at the POS.