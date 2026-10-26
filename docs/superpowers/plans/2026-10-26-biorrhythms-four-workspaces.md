# Biorrhythms Four Workspaces Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use `superpowers:executing-plans` for native execution, or `superpowers:subagent-driven-development` if the user selects delegated execution. Implement one task at a time and review its manual evidence before proceeding. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Reorganize Biorrhythms into four calm, accessible spaces — Lectura, Explorar, Diario and Herramientas — while preserving calculations, public contracts and local data.

**Architecture:** Keep the current PHP server-rendered entry point and vanilla JavaScript/CSS. Move each workspace's markup into a guarded PHP partial, use one shell/navigation controller to switch the visible workspace and map existing anchors, and keep the existing calculation/rendering code responsible for values. Change only presentation and client-side orchestration; preserve API payloads, query semantics, localStorage schemas, calendar, widget and PWA behavior.

**Tech Stack:** PHP 8.3 Docker image (`php:8.3-cli-alpine`), PHP 8.2+ project floor, vanilla JavaScript, CSS, SVG, existing PHP API and service worker. No new dependency or frontend framework.

**Spec:** `docs/superpowers/specs/2026-10-26-biorrhythms-four-workspaces-design.md`

## Global Constraints

- Interface copy stays in Spanish; preserve the name **Biorrhythms** and the approved **Bitácora de observación** visual direction.
- Keep the three sinusoidal calculations and their semantics unchanged; values remain mathematical outputs, not measurements, diagnoses or validated predictions.
- Keep birth date, preferences and diary data in browser storage; add no account, server profile, telemetry or data migration.
- Preserve `birth`, `focus`, `partner_birth`, `preset`, `embed`, `view`, `theme` and every other query parameter already recognized; `view=widget` continues to mean widget mode.
- Preserve `/api/` and `/api/v1/` routes, fields, status codes and response semantics; retain compatibility, ritual and other server/API outputs even when their current UI cards are retired.
- Preserve `.ics`, PNG/share, widget, notifications, PWA and local import/export/forget behavior.
- Do not add a frontend dependency or change the PHP runtime. Do not change math or API contracts as part of this visual/IA redesign.
- Add or run automated tests only if the user asks; use PHP syntax checks, API contract spot checks, manual browser flows and visual review for this implementation.
- Do not implement on the current dirty `main` checkout. At execution time, inspect local changes and select the correct released base; use an isolated worktree and preserve the user's current checkout and untracked documents.

## Review Focus

- Empty localStorage and no `birth` query: render one onboarding date field, no sample values as personal, and no interactive secondary workspace before setup (Task 3).
- Saved profile with `focus`, `partner_birth`, `preset`, `theme` and unrelated query parameters: switching workspaces and changing dates preserve relevant query state; the profile remains local (Tasks 2–3, 6).
- Direct legacy fragments (`#monthCalendar`, `#diary`, `#chrono`, `#prefs`, `#context`) and `?view=widget`: open the right workspace/anchor or the unchanged embed mode (Tasks 2, 4–6).
- Empty diary, fewer than 14 records, constant values, invalid `birth`/`focus`/`partner_birth`/`preset` and a full-cycle extreme-day list: show accurate empty/error states and never dump all results by default (Tasks 3–6).
- 320 px viewport, mobile landscape, 390 px viewport, 768 px and 1440 px; keyboard and reduced-motion preference: no overflow, obscured controls or focus trapped in hidden content (Task 7).

---

## File Map

- Modify `index.php`: calculate initial PHP data plus the Explore-only 135-day-radius window and birth-anchored full-cycle extreme list; enforce first-visit rendering; compose the four workspaces; keep existing interaction logic and update references affected by retired UI.
- Create `views/header.php`: shared brand masthead and theme control.
- Create `views/navigation.php`: four-destination navigation in the shared app shell.
- Create `views/reading.php`: one-time setup state and the primary date/value/chart experience.
- Create `views/explore.php`: event horizon controls, calendar and progressive full-cycle list.
- Create `views/diary.php`: existing diary markup and local-data actions.
- Create `views/tools.php`: comparison, chronotype, symbolic context, sharing/integrations and preferences.
- Create `assets/workspaces.js`: workspace activation, hash/history synchronization, legacy-fragment mapping and focus management. This file owns navigation only; it does not calculate rhythm values or mutate profile data.
- Create `assets/workspaces.css`: shell, nav, workspace visibility and responsive rules. Keep unrelated existing component styles in `index.php` during this change.
- Modify `sw.js`: precache the two new static assets and use the release cache version `v1.7.0` after confirming the selected implementation base is the released v1.6.0 line.
- Modify `PRODUCT.md`: document the four workspaces, neutral model framing, feature disposition and preserved local-only data behavior. This file is currently untracked in the inspected checkout; carry it into the implementation branch deliberately rather than assuming it exists on remote `main`.
- Create `.impeccable/review/reading-desktop.png`, `reading-mobile.png`, `explore-mobile.png`, `diary-mobile.png`, and `tools-mobile.png` as local review evidence; do not ship these screenshots as application assets.

### Shared markup/controller contract

- Each view root is a `<section id="<slug>" data-workspace-panel="<slug>" aria-labelledby="<slug>-heading">` for `lectura`, `explorar`, `diario` and `herramientas`.
- Each nav link uses `href="#<slug>"`, `data-workspace-link="<slug>"` and an accessible current-state attribute managed by the controller.
- `assets/workspaces.js` keeps its helpers private in an IIFE. It reads only the above data attributes, fragment state and static alias map. It exports no global API.
- Canonical fragments are `#lectura`, `#explorar`, `#diario` and `#herramientas`. Legacy fragment aliases are `monthCalendar → explorar`, `diary → diario`, and `chrono|prefs|context|compat|sharePreview|widgetSnippet|apiPreview → herramientas`; the controller scrolls/focuses the matching target after activation when it exists.
- An inactive workspace is made both visually hidden and unavailable to keyboard/screen-reader navigation. Without the controller, all content remains visible as a long page with working anchors.
- In `embed-view`, omit the four-item navigation and let the existing widget remain the only visible surface. Keep all DOM hooks required by the current inline renderer present so the embed does not throw while hidden content is styled away.
- Keep existing IDs consumed by `index.php` and `assets/{diary,chrono,notify,profile}.js` unless the same task updates every consumer. Never duplicate an ID across partials.

## Task 1: Extract four view partials without changing behavior

**Files:**
- Modify: `index.php` (main markup from `main.shell`, lines around current hero/workspace sections, and asset links)
- Create: `views/header.php`
- Create: `views/reading.php`
- Create: `views/explore.php`
- Create: `views/diary.php`
- Create: `views/tools.php`

**Interfaces:**
- Consumes: all existing PHP variables prepared before HTML output, including `$showWelcome`, `$birthInput`, `$focusInput`, `$seriesData`, `$month`, `$icsLink`, `$widgetUrl`, `$apiUrl`, and existing feature state.
- Produces: the same current page content divided into four server-rendered view roots; no navigation hiding or feature removal in this task.

- [ ] **Step 1: Define the partial-entry guard.** In `index.php`, define `BIORRHYTHMS_ENTRYPOINT` after `declare(strict_types=1);`; start every file in `views/` by returning HTTP 404 and exiting when the constant is absent. This protects fragments because Docker's PHP server uses the repository root as its document root.
- [ ] **Step 2: Move the existing brand masthead and theme control** into `views/header.php`; preserve `themeToggleBtn` and all classes used by theme CSS. The four-link navigation is added in Task 2.
- [ ] **Step 3: Move the existing first-visit, hero, controls, disclaimer, chart and `detail-grid` markup** into `views/reading.php`; preserve existing IDs during extraction.
- [ ] **Step 4: Move `events-card`, `extreme-card` and `monthCalendar` markup** into `views/explore.php`; preserve IDs `eventsList`, `eventsPill`, `extremeGrid`, `extremePill`, `monthCalendar` and all links.
- [ ] **Step 5: Move `diary` markup** into `views/diary.php`; preserve `diaryForm`, `diaryDate`, `diaryEnergy`, `diaryMood`, `diaryFocus`, `diaryRows`, import/export/forget controls and status IDs.
- [ ] **Step 6: Move compatibility, decision, ritual, context, chrono, prefs, story, share, widget and API markup** into `views/tools.php`; preserve every live JS ID and existing anchor ID until Task 6 removes obsolete UI consumers.
- [ ] **Step 7: Replace the moved markup in `index.php` with static `require __DIR__ . '/views/<name>.php';` calls** and the four `data-workspace-panel` wrappers. Do not choose a partial from query-string input.
- [ ] **Step 8: Verify PHP syntax** with `php -l index.php` and each `views/*.php`; open the app and confirm the initial page still renders all existing sections before enabling workspace hiding.
- [ ] **Step 9: Verify direct requests** to `/views/reading.php` and `/views/tools.php` return 404 and produce no PHP warnings; verify `?view=widget` still displays the widget alone and has no console errors.

## Task 2: Add accessible workspace navigation and stable fragment state

**Files:**
- Modify: `index.php` (HTML root class/bootstrap and head assets)
- Create: `views/navigation.php`
- Modify: each `views/*.php` workspace root/heading
- Create: `assets/workspaces.js`
- Create: `assets/workspaces.css`
- Modify: `sw.js` (precache and `VERSION = 'v1.7.0'`)

**Interfaces:**
- Consumes: Task 1's `data-workspace-panel`, `data-workspace-link`, workspace IDs and existing feature anchors.
- Produces: a private `activateWorkspace(id, {focusHeading, historyMode})` implementation in `assets/workspaces.js`; canonical fragments and the responsive four-item navigation. No existing calculations or form state are changed.

- [ ] **Step 1: Add semantic navigation** with four text-labelled links, `aria-label="Secciones principales"`, a visible current state and headings with IDs matching the shared markup contract.
- [ ] **Step 2: Add progressive enhancement.** Keep all four panels visible without JavaScript. Load `assets/workspaces.js` with `defer`; after parsing, it resolves the initial hash and then applies `hidden` to inactive panels. Do not add a class or CSS rule that hides content if the controller has not loaded successfully.
- [ ] **Step 3: Implement `activateWorkspace(id, {focusHeading = false, historyMode = 'none'} = {})`** inside an IIFE. It validates `id`, toggles panel `hidden`, updates nav `aria-current`, focuses the new heading with `tabindex="-1"` when requested, and uses `history.pushState` only for a user-initiated workspace change.
- [ ] **Step 4: Resolve URL state.** On load and `hashchange`, map canonical fragments, exact legacy aliases, or a real target's closest `[data-workspace-panel]`; unknown fragments with no matching element fall back to Lectura. On `popstate`, resynchronize visible view. Selecting a nav link writes its canonical fragment; `?view=widget` is not read or changed by this controller. Exit without changes when `document.body.classList.contains('embed-view')` or when the onboarding has no workspace navigation.
- [ ] **Step 5: Add static fragment aliases** to their actual target elements and ensure legacy links activate a view before scrolling/focusing the target. Preserve the original anchor identifier on each feature; add `tabindex="-1"` only to legacy targets that otherwise cannot receive programmatic focus.
- [ ] **Step 6: Implement fallback behavior** so JS-disabled users see all sections in document order and every nav link scrolls to its workspace heading.
- [ ] **Step 7: Implement initial responsive nav styles** in `assets/workspaces.css`; add both files to `sw.js` `PRECACHE` and set `VERSION` to `v1.7.0` for the v1.6.0 base.
- [ ] **Step 8: Preserve embed rendering.** In `embed-view`, skip workspace activation and hide navigation plus every panel except `#widget`. Keep the widget card as a direct child of the Tools panel so scoped CSS can reveal it alone; update the old `.shell > :not(.widget-card)` rule, which would otherwise hide its new parent. Do not remove JS dependencies that the current inline renderer needs.
- [ ] **Step 9: Run `php -l index.php`; manually verify** all four links, visible selected state, direct canonical and legacy fragments, reload, back/forward, keyboard activation, no-JS anchor fallback and isolated `?view=widget` output.

## Task 3: Make Lectura the complete personal flow

**Files:**
- Modify: `index.php` (first-visit server state, date URL synchronization, `updateSelected`, `updateForecast`, share-summary date)
- Modify: `views/reading.php`
- Modify: `views/tools.php` (personal birth-date form)
- Modify: `assets/workspaces.js` only if a date link must activate Herramientas

**Interfaces:**
- Consumes: current `$showWelcome`, `assets/profile.js`, `data.window`, `data.selectedIndex`, existing slider and SVG renderer.
- Produces: one first-visit birth form; configured Reading view with selected date, three values, graph and neutral prompt; query `focus` mirrors the selected chart date without losing other query values or the active fragment.

- [ ] **Step 1: Render onboarding-only state when `$showWelcome && !$embedMode`.** Do not render example stats, chart, event details or secondary workspaces as personal content; include the brand masthead and one labelled birth-date form, with no theme toggle or workspace navigation. Skip secondary PHP partials and all main-app/feature initialization scripts on this state so their required DOM hooks are not missing. Leave the existing `assets/profile.js` head resolver active; after form submission it stores the valid date and the next render opens Lectura.
- [ ] **Step 2: Make configured Reading show one value set.** Keep `stat-physical`, `stat-emotional`, `stat-intellectual`, `windowSlider`, `selectedLabel`, `grid`, `paths` and `markers`; remove the duplicate `detail-grid` presentation and update `updateSelected(index)` to stop querying removed `detail-*` IDs.
- [ ] **Step 3: Keep date-of-birth editing in Tools.** Move the current `birth` GET field out of `controls-bar`; its form submits selected birth and focus plus hidden partner/preset state and targets `#herramientas` so the user returns to the same workspace. Keep the focus-date control in Reading. Preserve the `assets/profile.js` rule that a real birth from the URL is stored locally and never persist the demo default.
- [ ] **Step 4: Add previous-day, next-day and today controls** adjacent to the date/slider. Implement `selectReadingDate(index)` as the single UI entry point: clamp to `data.window`, set the slider, call `updateSelected(index)` and update the address bar's `focus` value. Route the slider's animation-frame handler through this function rather than calling `updateSelected` directly.
- [ ] **Step 5: Define `selectedFocusDate()`** as the selected timeline point's ISO date with `focusInput` as a defensive fallback. Use it for share copy, the API preview, widget link and compatibility URL state. Centralize URL synchronization in `syncReadingUrl(focusDate = selectedFocusDate())`: update `birth` only when `birthIsExampleInitial` is false; set `focus`; preserve the existing `partner_birth`/`preset` handling, unrelated query parameters and current fragment; use `replaceState` during slider movement to avoid one history entry per drag. Split API/widget-preview refresh from URL mutation so dragging does not issue an API fetch on every animation frame; refresh integration previews once when their view is opened or a date change is committed.
- [ ] **Step 6: Replace `heroForecastChips` best/worst output** with the fixed neutral prompt in the spec. Decouple that element from `updateDecisionAssistant` so it cannot be overwritten by forecast rendering. Remove visual copy such as «mejor», «valle», «listo para decidir» and instructions driven by the cycle score from Reading.
- [ ] **Step 7: Keep the model limitation adjacent to the values.** Copy: `Porcentajes calculados por un modelo matemático; no miden energía ni predicen resultados.` Keep the full existing evidence/legal-use disclaimer visible in Reading, with symbolic-framework caveat moved to the optional Herramientas context module.
- [ ] **Step 8: Verify first visit and returning visit manually.** With empty localStorage and no `birth`, expect one birth field and no sample values. Submit a valid date, expect today's local reading and saved profile. Reload, expect direct Reading. Move date by slider/buttons, expect values, chart marker, share-card date and `focus` query to agree; confirm other query values and current fragment survive.
- [ ] **Step 9: Verify invalid `birth`/`focus` still shows the existing validation notice** and does not save an invalid value to localStorage.

## Task 4: Move temporal analysis to Explorar and bound its default density

**Files:**
- Modify: `index.php` (event/extreme rendering and `$monthLink` fragment preservation)
- Modify: `views/explore.php`
- Modify: `assets/workspaces.js` (calendar links when needed)
- Modify: `assets/workspaces.css`

**Interfaces:**
- Consumes: existing `data.window`, new page-only `data.exploreWindow` and `data.cycleExtremeDays`, `eventsList`, `extremeGrid`, `MonthCalendar`, `$icsLink`, `$monthLink` and calendar query parameters.
- Produces: 7/30/90-day event horizons, 30-day default summary, and explicit progressive access to the full-cycle list without changing `ExtremeDays`, API output or `.ics` semantics.

- [ ] **Step 1: Add labelled horizon controls** using `data-explore-horizon="7|30|90|cycle"`; select `30` initially and expose the selected value with `aria-pressed`.
- [ ] **Step 2: Add `data.exploreWindow`** from `Timeline::window($bio, $birthDate, $focusDate, ApiPayload::WINDOW_RADIUS + 90)` to page-only `$seriesData`. Do not replace or enlarge `data.window`, which remains the chart and compatibility window. The wider window ensures 90 future days are available even when the selected chart point is 45 days after the server focus.
- [ ] **Step 3: Change `buildSpecialEvents(horizonDays)`** to filter `data.exploreWindow` from the selected focus date through focus + horizon, group events by date and cycle, and render an explicit empty state when no events match. Keep peak/valley/zero-crossing definitions unchanged.
- [ ] **Step 4: Anchor the UI full-cycle collection at birth.** Prepare page-only `data.cycleExtremeDays` with `ExtremeDays::find($bio, $birthDate, $birthDate, $extremeThreshold)`; leave `ApiV1` and its request-focus `extreme_days` semantics untouched. In `buildExtremeDays`, filter this collection to dates on/after the selected focus and within 30 days by default. Render at most five previews, then a labelled `Ver ciclo completo` action that reveals the first ten items; subsequent activation reveals ten more with the label `Mostrar 10 más`. Provide `Mostrar menos` after expansion; never inject the full list on initial render.
- [ ] **Step 5: Keep calendar month navigation within Explorar.** Preserve `birth`, `focus`, `partner_birth` and `preset` in `$monthLink`; append `#explorar` to calendar links. Keep the `.ics` link and its current 90-day contract.
- [ ] **Step 6: Update event horizon and full-cycle filtering** whenever `selectReadingDate` changes the selected date; month navigation reloads with the new focus. Do not change calculation inputs or the daily profile.
- [ ] **Step 7: Update the `.ics` link's `from` date** to the selected ISO focus while preserving its 90-day `days` contract.
- [ ] **Step 8: Verify manually** 7/30/90-day horizons, empty horizon, full-cycle reveal/expand/collapse, selection near both ends of the chart window, month navigation and `.ics` export. Confirm the default view never renders dozens of future cards and `#monthCalendar` opens Explorar at the calendar.

## Task 5: Make Diario a focused, privacy-clear workspace

**Files:**
- Modify: `views/diary.php`
- Modify: `index.php` only for diary output text if needed to state correlation limits
- Modify: `assets/workspaces.css`
- Do not modify: `assets/diary.js`, diary storage schema, or correlation formulas unless direct visual integration requires a minimal selector-preserving change.

**Interfaces:**
- Consumes: existing `window.BiorrhythmsDiary`, DOM IDs, `localStorage` key/schema, correlation implementation and current CSV/JSON controls.
- Produces: a single-purpose diary workspace with understandable record, history, correlation, export/import and forget states.

- [ ] **Step 1: Move the existing diary form, table and controls** into `views/diary.php` without changing IDs or field names.
- [ ] **Step 2: Add the explanatory copy** beside the correlation result: it compares this person's self-reported records with model values and does not establish cause or predictive validity.
- [ ] **Step 3: Preserve empty, insufficient-sample and constant-data states.** Fewer than 14 records must not show a correlation as a conclusion; constant inputs must retain the existing no-correlation warning.
- [ ] **Step 4: Keep export/import/forget controls together** under an explicit data section; label that records remain in this browser and that forget is irreversible for the local copy.
- [ ] **Step 5: Verify manually** create/edit records, reload and confirm persistence, export/import a sample JSON, forget local diary, and check empty/insufficient states. Confirm data never appears in URL or a server request.

## Task 6: Consolidate Herramientas and retire misleading UI-only flows

**Files:**
- Modify: `views/tools.php`
- Modify: `index.php` (compatibility labels/copy, front-end function calls/listeners and page-only `$seriesData` fields)
- Modify: `assets/workspaces.css`
- Do not modify: `api/index.php`, `api/v1/index.php`, `src/Compatibility.php`, `src/DailyReading.php`, API payload fields or URL parameter names.

**Interfaces:**
- Consumes: existing compatibility/chart/chrono/notification/share/widget/API markup and JavaScript consumers.
- Produces: tools organized into four grouped areas: Comparar ciclos; Cronotipo y horario; Contexto simbólico; Compartir e integrar. Profile and preferences are a compact disclosure within Herramientas, not a fifth top-level destination.

- [ ] **Step 1: Move all preserved tools** into the Tools partial and group them under visible subheadings, initially retaining their IDs and initialization order.
- [ ] **Step 2: Reframe comparison copy** as mathematical pattern overlap. Replace “Modo compatibilidad”, “Score de compatibilidad” and “match” UI language; state that the result does not evaluate interpersonal compatibility. Keep `compatScore`, `compatNarrative`, `partnerBirthInput`, presets, existing URL fields and export controls functional.
- [ ] **Step 3: Separate symbolic context and chronotype limitations.** Show the symbolic/non-empirical note within Contexto simbólico. Show the non-diagnostic/non-validated-questionnaire note with the chronotype result.
- [ ] **Step 4: Retire decision-assistant, ritual and Story mode as standalone UI.** Remove their markup and UI-only generation/animation/copy handlers. Specifically update `updateForecast(index)` so it no longer calls `updateDecisionAssistant`, `updateRitual` or `updateStoryMode`; remove direct listeners for deleted `copyRitualBtn` and `storyAutoBtn`; remove calls to deleted `decision-*`, `ritual-*` and `story-*` IDs from `updateSelected` and initialization. Retain `DailyReading`/ritual fields returned by APIs and any calculation helpers still consumed by server/API code.
- [ ] **Step 5: Add one static neutral reflection question** in Lectura. Do not derive its wording from `data.rituals`, `decisionForPoint`, best/worst forecasts or any score.
- [ ] **Step 6: Keep share PNG/link, widget iframe, API preview, theme, notifications and data controls available** in their groups. Preserve direct UI initialization and event handlers when their DOM moves to this hidden view; do not remove or hide the widget DOM needed by `?view=widget`. Label shared cards as a neutral «Lectura del día», not a «Pico»; make copied reading links point to `#lectura` even when copied from Herramientas.
- [ ] **Step 7: Confirm listener integrity by inspection and browser console.** Search all `getElementById`/`querySelector` references for removed IDs; remove or redirect every consumer before deleting markup. `updateSelected` must still update `stat-*`, `share*`, chart markers and the reading date without throwing.
- [ ] **Step 8: Verify manually** valid and invalid partner dates/presets, comparison/export, chronotype form/result, symbolic context, share PNG/link, widget preview, API preview, theme and notifications. Direct API response still contains the same compatibility/ritual fields and status behavior.

## Task 7: Finish the responsive and accessible visual system

**Files:**
- Modify: `assets/workspaces.css`
- Modify: `index.php` (existing inline component styles only where integration requires it)
- Modify: `views/navigation.php` and view templates for semantic/focus fixes
- Modify: `sw.js` (only if final asset list or cache version changed)
- Create/update: `.impeccable/review/*` captures

**Interfaces:**
- Consumes: completed workspace shell/content and approved Bitácora color/typography direction.
- Produces: four focused spaces at desktop and mobile, no horizontal overflow, visible focus and reduced-motion support.

- [ ] **Step 1: Style desktop shell** with a compact masthead and four text-labelled destinations; mark active workspace by typography/rule/surface, not by adding more decorative cards.
- [ ] **Step 2: Style mobile navigation** as a bottom bar with four visible labels, icons that supplement rather than replace text, minimum 44×44 CSS px hit targets and `env(safe-area-inset-bottom)` padding. Ensure the bar never covers the last control or keyboard focus.
- [ ] **Step 3: Rebuild Reading density** so the date and three model values fit at 390×844, with chart beginning immediately afterward. Add no repeated value cards or forecast/decision chips.
- [ ] **Step 4: Ensure Explore and Tools use progressive disclosure** for long data, snippets, compatibility heatmap and full-cycle entries. Closed content must be absent from keyboard order and screen-reader browse order.
- [ ] **Step 5: Verify focus behavior and semantics**: nav links have visible focus; selected destination uses `aria-current="page"`; hidden panels use `hidden`; activation focuses the workspace heading; no focus remains inside an inactive panel.
- [ ] **Step 6: Verify responsive layouts and reduced motion** at 320, 390, 768 and 1440 px, plus 390 px landscape, with reduced-motion enabled. Confirm no horizontal page overflow, clipped dates/buttons, overlap with fixed nav or animation-dependent state.
- [ ] **Step 6a: Check color contrast** for text and controls against their actual surfaces: at least 4.5:1 for normal text and 3:1 for large text and essential UI indicators; verify focus is visible in both themes.
- [ ] **Step 7: Capture review screenshots** for Reading desktop/mobile and one mobile capture for each secondary workspace; visually inspect for empty panels, duplicated values, card proliferation and inconsistent hierarchy.

## Task 8: Product/docs alignment and final manual integration review

**Files:**
- Modify: `PRODUCT.md`
- Modify: `README.md` only if user-facing descriptions currently claim the retired decision/ritual/story UI is a primary capability
- Review: `index.php`, `views/*.php`, `assets/workspaces.js`, `assets/workspaces.css`, `sw.js`

- [ ] **Step 1: Update `PRODUCT.md`** so its primary task, four spaces, labels for non-predictive results, retired UI flows and still-available secondary tools match the approved spec.
- [ ] **Step 2: Review copy alignment** in `README.md`; replace the old hero/decision/ritual descriptions with the four workspace map, describe the event list as progressive in Explorar, and separate the primary UI copy from API fields retained for compatibility. Do not rewrite API documentation or change contract examples.
- [ ] **Step 3: Run PHP syntax checks** with `php -l index.php` and each PHP partial. Expected: no syntax errors.
- [ ] **Step 4: Perform manual local Docker review** from a clean browser profile and an existing profile at desktop/mobile sizes. Confirm first visit, returning visit, all four workspaces, history/anchors, date selection, all retained tool flows, and JS console has no errors.
- [ ] **Step 5: Perform manual API contract spot checks** for `/api/` and `/api/v1/` using the pre-change baseline: same field names, status codes, compatibility/ritual fields, URL semantics and invalid-date behavior.
- [ ] **Step 6: Verify PWA asset refresh** after the `v1.7.0` cache bump; install/update the service worker and confirm `assets/workspaces.js` and `assets/workspaces.css` load both online and after a successful online visit followed by offline reload.
- [ ] **Step 7: Review the final diff** against the spec and confirm it contains no formula, API, localStorage schema, query parameter or unrelated visual-system changes.

## Self-Review

- **Spec coverage:** four destinations/navigation (Tasks 1–2); first visit and core daily flow (Task 3); bounded date exploration/calendar/full-cycle disclosure (Task 4); local diary/correlation language (Task 5); comparison/symbolic/chronotype/integration and retirement of UI-only recommendations (Task 6); mobile, keyboard, focus and reduced motion (Task 7); product/docs, contracts, PWA and final manual review (Task 8).
- **Review Focus mapping:** empty profile → Task 3; query/local profile preservation → Tasks 2–3 and 6; legacy anchors/widget → Tasks 2 and 4–6; empty/invalid data and long extreme list → Tasks 3–5; responsive/keyboard/motion → Task 7.
- **Type/interface consistency:** all view/controller selectors use the shared `data-workspace-*` contract; no JS global API is introduced. Existing feature IDs are retained unless every caller is removed in Task 6.
- **Scope:** no new framework, endpoint, data model, formula or server persistence. PHP templates isolate markup; workspace CSS/JS own only navigation and layout. Existing feature logic remains in place unless it exists solely for the retired decision/ritual/story presentation.
- **Verification constraints:** no automated tests are added or run by this plan; use syntax checks, browser/manual interactions, API contract spot checks and visual evidence.
- **Execution base:** inspected checkout is `main` behind `origin/main` with local modifications and untracked work. Before implementation, create an isolated worktree from the intended released base and deliberately carry forward the approved spec, this plan and `PRODUCT.md`; do not reset or clean the current checkout.
