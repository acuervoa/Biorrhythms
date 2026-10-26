# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Individuals consulting a Spanish-language web app to understand their own reading for a selected day. The experience should work for people who have never used a biorhythm model; comparing two profiles is a secondary use case.

## Product Purpose

Help a person understand the model's three calculated values for a selected day and explore their pattern over time as a prompt for personal reflection.

## Positioning

Biorrhythms presents three classic sinusoidal cycles—physical (23 days), emotional (28 days), and intellectual (33 days)—alongside optional symbolic context and reflective daily prompts. Its values are mathematical outputs, not measurements of biological energy, diagnoses, or predictions.

## Operating Context

- The primary task is understanding one person's reading for a selected day, then exploring the chart and nearby dates.
- The interface has four destinations: **Lectura** (daily values, chart and date selection), **Explorar** (events, calendar and bounded cycle extremes), **Diario** (private self-observation) and **Herramientas** (comparison, chronotype, symbolic context, sharing and integrations).
- First use asks for a birth date once, then opens the person's own reading without sample data that could be mistaken for theirs.
- The app is used on mobile and desktop browsers.
- Birth date, preferences, and diary entries are stored in the browser. There is no account or server-side profile.
- The interface is in Spanish.

## Capabilities and Constraints

- Calculate the three biorhythm model values and chart them over time. Keep one neutral reflection question in the primary reading; do not recommend actions or frame a forecast as advice.
- Show event horizons of 7, 30 or 90 days and a concise 30-day cycle-extreme preview. Let people reveal the remaining cycle results in labelled batches.
- Preserve monthly calendar, local self-observation diary, chronotype guidance, symbolic context (zodiac, numerology, and approximate lunar phase), sharing/export, embeddable widget, PWA behavior, and API routes.
- Describe two-profile comparison as mathematical pattern overlap; it does not evaluate interpersonal compatibility.
- Keep decision-assistant, generated ritual and Story mode out of the primary interface. Retain their existing API fields for consumers that depend on them.
- Keep symbolic context optional and clearly distinguished from biorhythm calculations.
- Preserve calculation semantics, API contracts, URL parameters, exports, and local-only data storage through the visual redesign.
- Do not present model values as measured energy or use them to support medical, financial, legal, or other consequential decisions.

## Brand Commitments

- Product name: **Biorrhythms**.
- The product needs a new visual identity; no external brand reference or visual system is binding.
- Keep the interface language Spanish.

## Evidence on Hand

- The repository contains the PHP implementation, UI copy, and formulas for the three model cycles.
- The product currently states that biorhythms have no demonstrated predictive validity and that astrology/numerology are symbolic frameworks, not empirical evidence.
- No user research, testimonials, or predictive evidence has been supplied. Do not invent any.

## Product Principles

1. Make today's reading understandable before inviting exploration.
2. Treat biorhythm values as model outputs and reflection prompts, never as measurements or predictions.
3. Keep symbolic content optional and visibly separate from the calculated rhythms.
4. Preserve the user's local control of personal data.
5. Keep advanced tools grouped in Herramientas and reveal secondary detail on request.

## Accessibility & Inclusion

The web experience must work on mobile and desktop. No formal accessibility conformance target has been specified; the redesign should preserve semantic labels, keyboard access, visible focus, and reduced-motion support.
