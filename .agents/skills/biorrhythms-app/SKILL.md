---
name: biorrhythms-app
description: Use whenever a task changes, reviews, documents, deploys, or releases the Biorrhythms application. Read its product and visual contracts, preserve mathematical and URL/API behavior, and follow the established daily-reading UX and release integration with PERS-Lab.
---

# Biorrhythms application

Apply this skill to changes in this repository. The root `PRODUCT.md` owns product behavior; `DESIGN.md` owns the visual tokens and visual rules. Read those files before making a user-facing change.

## Product contract

- The main experience is for one individual. First visit requests that person's birth date once; configured visits open the reading for a selected day.
- The model calculates three sinusoidal cycles: physical (23 days), emotional (28 days), and intellectual (33 days). Present values as mathematical outputs, never as biological measurements, medical guidance, or validated predictions.
- Keep birth date, preferences, and diary data in browser storage. Do not add accounts or server-side personal profiles without explicit product direction.
- Preserve the `birth`, `focus`, `partner_birth`, and existing query parameters; `/api/v1/` contract; legacy `/api/` behavior; widget/embed parameters; export; and secondary tools.
- The app has four independent destinations: Lectura, Explorar, Diario and Herramientas. Keep each destination focused and restore it from URL fragments; legacy section fragments must continue to reveal their containing destination.
- Keep diary records, profile data and preferences in browser storage. Date/profile GET forms must carry unrelated query parameters through navigation.
- Explore uses selectable 7/30/90-day event horizons, a 30-day cycle-extreme preview and progressive reveal. Never create unbounded initial lists.
- Tool comparisons describe mathematical pattern overlap; keep interpersonal compatibility claims and action recommendations out of the UI. Retain existing API fields for compatibility.
- Keep Spanish user-facing copy and named cycle labels alongside color.

## Visual and UX direction

- The primary flow is date → three actual model values → chart for that date → neutral reflection. The four destinations provide a clear separation between reading, exploration, diary and tools.
- Follow `DESIGN.md`: warm paper, dark ink, Georgia-style editorial serif, Avenir/system sans, and cycle colors physical terracotta, emotional teal, intellectual blue. Keep light/dark themes consistent.
- First visit has one required birth-date field and no illustrative values presented as personal data.
- The chart marker indicates the selected date, which may differ from today. At viewport widths up to 700px, keep the X-axis to at most five date labels; redraw its grid when resizing.
- Responsive navigation becomes a fixed four-destination bottom bar at widths up to 820px; account for safe areas and content inset. Responsive review should cover desktop and 320/390px mobile, tablet and landscape in light and dark themes. Keep controls usable and prevent page-level horizontal overflow.
- Workspace changes must update the URL, reveal the correct panel, focus its heading and bring it into view. Respect reduced-motion preferences and support browser back/forward.
- Approved comps guide composition, but preserve actual model values and required product disclosures when their text differs from illustrative mock content. Never change app data just to improve pixel-diff scores.

## Implementation and verification

- Keep the existing PHP, vanilla JavaScript, CSS, Docker, and asset conventions. Do not add a dependency for a visual-only change.
- Before editing, inspect current Git changes and the affected flow. Make focused, reversible changes and preserve unrelated work.
- For UI changes, inspect a real browser capture after the last edit. For syntax and hygiene, use `php -l index.php` and `git diff --check`. Run automated tests only when the user asks for testing or verification.
- Keep `README.md`, `PRODUCT.md`, `DESIGN.md`, and `CHANGELOG.md` consistent with the delivered behavior. Store dated specs and implementation plans under `docs/superpowers/`.

## Release and PERS-Lab

- Bump `sw.js` `VERSION` when making a release so installed PWAs move to a new cache namespace.
- Update `CHANGELOG.md`, verify the actual version/tag does not already exist, and use a semantic version consistent with the change.
- For the four-workspace redesign release, update Biorrhythms in PERS-Lab `APPLICATIONS` with the published tag using its optional `version` field. Preserve any dirty primary checkout by working in an isolated worktree based on current `main`.
- PERS-Lab already lists Biorrhythms in `APPLICATIONS`; expose the released tag using its optional `version` field when integrating a release.
- If the PERS-Lab checkout has local changes, work from an isolated worktree based on its current `main`; never switch, reset, clean, or overwrite the user's dirty checkout.
- Do not commit, tag, publish a GitHub release, or push unless the user explicitly authorizes those operations.

## Local domain

- Treat `biorrhythms.local` name resolution and HTTP routing as separate layers. A user systemd unit runs Avahi's publisher; Nginx serves the host and proxies to the Docker-published `127.0.0.1:8002` port.
- Keep the Avahi unit in `deploy/systemd/biorrhythms-local-alias.service`; install it under `~/.config/systemd/user/`, reload the user manager, and enable it. The current host address follows the existing local aliases at `192.168.1.175`.
- Keep the virtual host in `deploy/nginx/biorrhythms.local.conf`. Inspect `nginx -T` first and install it to `/etc/nginx/conf.d/` only when no existing `server_name biorrhythms.local` block is loaded; then run `nginx -t` and reload Nginx on the host. Avahi alone cannot route HTTP to the app; do not add `/etc/hosts` entries.
- Verify name resolution with `getent ahostsv4 biorrhythms.local`. Verify virtual-host selection independently with `curl --resolve biorrhythms.local:80:127.0.0.1 http://biorrhythms.local/` and confirm the response is Biorrhythms, not the default site.

## Completion

Report the changed contracts, verification actually run, release tag and URL if published, PERS-Lab integration commit, and any limitations. Preserve worktrees and branches unless the user explicitly asks to remove them.
