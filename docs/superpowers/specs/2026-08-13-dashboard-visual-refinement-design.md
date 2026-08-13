# Dashboard Visual Refinement Design

## Direction

Refine the existing role dashboard to match the supplied reference while retaining SKSU branding and the current Blade/Tailwind architecture. The result should feel like a calm academic administration product: spacious, crisp, readable, and practical rather than decorative.

## Layout

- Use a 21–22rem desktop sidebar with a clear brand block, comfortable navigation spacing, and a bottom-anchored logout action.
- Use a white page header with stronger vertical padding and a compact role badge.
- Use a soft gray application background and a wider content container with consistent horizontal gutters.
- Increase vertical separation between the welcome panel, metrics, charts, and supporting sections.
- Keep responsive behavior mobile-first: sidebar becomes a normal top section, cards stack, and no content creates horizontal overflow.

## Typography and Spacing

- Use Inter as the primary interface font with a system fallback.
- Keep body copy at 16px where practical, with 1.5 line height.
- Use medium weight for navigation and labels, semibold for headings, and avoid excessive uppercase copy.
- Standardize primary card padding at 1.5rem on mobile and 1.75–2rem on larger screens.
- Use a 1.5rem primary section gap and 1rem metric-card gap.

## Components

- Sidebar links use 44px minimum height, 12–14px horizontal padding, and a dark SKSU green active state.
- Cards use subtle gray borders, a restrained shadow, and 10–12px corner radius.
- The welcome panel keeps its emerald left accent but gains more breathing room and a stronger heading hierarchy.
- Metric cards use clear labels, large tabular values, and small low-contrast status badges.
- Chart panels share the same card surface and header spacing.
- Buttons and disclosures retain visible focus rings and keyboard accessibility.

## Scope

This refinement changes the shared application shell, sidebar, and dashboard presentation only. It does not alter role permissions, queries, routes, chart data, or workflow behavior.

## Verification

Run role dashboard and UI security tests, the full PHP suite, formatting checks, and the Vite production build. Confirm the existing navigation labels and accessibility assertions remain valid.
