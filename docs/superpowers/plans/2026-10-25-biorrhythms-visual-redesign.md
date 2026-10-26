# Biorrhythms Visual Redesign Implementation Plan

> **For agentic workers:** Follow this plan task by task. Do not add or run tests; use the visual and syntax checks listed below.

**Goal:** Replace the current dashboard hierarchy with the approved “Bitácora de observación” identity and a clear personal daily-reading flow while preserving all existing behavior and contracts.

**Architecture:** Keep the current PHP-rendered page and its existing JavaScript/CSS stack. Reshape the `index.php` markup and styles in place, retaining the current calculations, DOM hooks, query parameters, API routes, local profile storage, and secondary feature implementations. Use the selected comp and measured `.impeccable/build/spec.json` as the desktop first-viewport contract; use semantic responsive layout outside that fixed desktop composition.

**Tech Stack:** PHP 8.3 Docker image (project requirement PHP 8.2+), vanilla JavaScript, embedded CSS, existing SVG chart.

**Spec:** `docs/superpowers/specs/2026-10-25-biorrhythms-visual-redesign-design.md`

## Global Constraints

- Keep all interface copy in Spanish and retain the product name **Biorrhythms**.
- Present percentages as mathematical model outputs, not biological measurements or validated predictions.
- Keep profile, preference, and diary data in browser storage; add no account or server profile.
- Preserve formulas, URL parameters, API contracts, exports, widget, PWA, and secondary features.
- Make the first visit request the birth date in one place, then show the person's reading for the local current day.
- Do not display illustrative comp values as user data.
- Do not add dependencies or alter runtime requirements.
- Do not add or run automated tests in this task; verify using syntax checks, browser captures, and manual flow review.

## Review Focus

- First visit with empty local storage: one birth-date field, no duplicate controls, and no example reading presented as personal.
- Returning visit with a saved profile: direct entry to the personal reading, including local-day URL behavior.
- Invalid or missing `birth`, `focus`, and compatibility inputs: preserve current validation and recovery behavior.
- Narrow mobile viewport: no page-level horizontal overflow, clipped reading values, or inaccessible secondary navigation.
- Embedded/widget mode and explicit URL parameters: preserve the current compact render and query semantics.

---

## File Map

- Modify `index.php`: server-rendered first-visit state, main reading hierarchy, navigation to secondary tools, design tokens, and responsive styling. Keep all existing calculation and feature code in place.
- Create `.impeccable/review/desktop.png` and `.impeccable/review/mobile.png`: visual evidence from the running local app; these are review artifacts, not shipped assets.
- Do not modify `assets/profile.js`, API files, calculation classes, Docker configuration, or persisted schemas unless direct inspection proves a markup hook cannot preserve current behavior. If that happens, record the reason before widening scope.

### Task 1: First-visit and daily-reading flow

**Files:**
- Modify: `index.php` (welcome markup around `welcomeForm`, `controls-bar`, and the main content start)

**Interfaces:**
- Consumes: existing `$showWelcome`, `$birthInput`, `$focusInput`, `$todayInput`, `Profile::labelEs()`, and `DailyReading` values.
- Produces: a welcome-only first-visit state with one required birth-date field; a configured state with the existing personal reading and one clear way to edit the birth date.

- [x] Keep the first-visit form as the only visible birth-date control while `$showWelcome` is true; suppress the dashboard and controls that currently duplicate the field.
- [x] After submission, use the existing GET URL and `assets/profile.js` local-storage behavior to load the selected profile and local current day.
- [x] On configured visits, show the actual selected date and computed values; retain visible model-language and the existing limits disclaimer.
- [x] Keep validation messages and recovery text connected to the form with semantic labels and alert semantics.
- [x] Run `php -l index.php` and inspect first-visit plus configured-visit states in the local browser; expected: one birth field before setup, real reading after setup, no sample percentages.

### Task 2: Primary reading, chart controls, and secondary-tool navigation

**Files:**
- Modify: `index.php` (header, main reading, chart controls, and existing secondary section wrappers)

**Interfaces:**
- Consumes: current date controls, timeline/slider state, chart SVG and zoom buttons, all current feature section IDs and JS event hooks.
- Produces: the “Bitácora de observación” hierarchy, clear Lectura/Explorar navigation, and an understandable route back to the primary reading.

- [x] Rebuild the primary viewport to match the approved comp's relative structure: compact masthead, date context, three labeled model values, broad temporal chart, and neutral reflection prompt.
- [x] Use real server-rendered values and existing chart data; preserve the fixed color mapping `#B84D39` physical, `#24776C` emotional, and `#4168A0` intellectual.
- [x] Keep date navigation, selected-day control, zoom, export, and return-to-current-day actions next to the chart/task they control.
- [x] Remove the redundant detail-value presentation if it repeats the three primary values; preserve any unique information it carries in an accessible place.
- [x] Organize existing events, extreme days, compatibility, calendar, diary, chronotype, symbolic context, sharing/export, widget, and API content under a clear secondary-tools path. Do not delete or rewrite their calculations or behavior.
- [x] Ensure all navigation targets and controls keep their existing IDs or are updated consistently with their JavaScript consumers.
- [x] Run `php -l index.php`; navigate through Lectura and Explorar; verify the chart changes with selected date and zoom, export remains reachable, and secondary sections remain reachable.
- [x] Open the existing widget/embed URL with explicit date and theme parameters; expected: compact embed behavior and query semantics remain intact.

### Task 3: Visual system, responsive behavior, and evidence review

**Files:**
- Modify: `index.php` (global/component styles, breakpoints, keyboard focus, reduced-motion handling)
- Create: `.impeccable/review/desktop.png`, `.impeccable/review/mobile.png`

**Interfaces:**
- Consumes: semantic markup and hierarchy from Tasks 1–2, approved comp, and `.impeccable/build/spec.json`.
- Produces: coherent light/dark presentation where supported, desktop and mobile layouts, and reviewable screenshots.

- [x] Replace the blue/glow dashboard treatment with warm paper surfaces, charcoal ink, restrained rules, editorial date/heading typography, stable cycle colors, and tabular values. Keep decorative effects secondary.
- [x] Preserve the theme control if it remains available; map both themes to the same identity and contrast system rather than dropping the behavior.
- [x] At mobile widths, replace the desktop date rail with a compact date sequence, stack the readings without clipping, make chart actions wrap cleanly, and keep secondary navigation usable without horizontal page scrolling.
- [x] Preserve visible keyboard focus, semantic names, non-color cycle labels, and reduced-motion behavior.
- [x] Verify the mobile overflow finding in a valid 390 px viewport; fix it only if reproduced.
- [x] Run `php -l index.php`, capture the local page at the comp size and a 390 px mobile viewport, inspect both captures, and compare desktop regions against `.impeccable/build/spec.json` and `.impeccable/mocks/decision/model-pick.png`.
- [x] Run the Impeccable detector once on the changed target and resolve mechanical findings; do not run it again.

## Self-Review

- Spec coverage: first-use setup and no sample reading (Task 1); prioritized main reading and secondary tools (Task 2); responsive, accessible visual system and confirmed overflow handling (Task 3); contracts and local-only storage are explicit constraints in every task.
- Scope: one source file is consistent with the existing PHP page and embedded CSS/JS. Supporting asset and API code stays untouched unless implementation evidence requires a narrow exception.
- Runtime: plan targets the existing PHP 8.3 Docker image and PHP 8.2+ project floor; no new runtime feature or package is needed.
- Verification: no automated tests will be added or run per the user's instruction. Syntax, real browser flow, responsive captures, and detector provide the planned evidence.
- Finish review: an independent reviewer recommended one fix for mobile X-axis label density. The chart now renders at most five dates below 700px; the follow-up reviewer confirmed separation in both themes. The automatic hero comparison remains discrepant where the comp uses illustrative copy/values; actual model outputs and product disclosures remain authoritative.
