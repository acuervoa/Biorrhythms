# Daily assistant commands design

## Goal
Define a small daily-use set of assistant commands that improve speed, quality, and understanding without automating changes blindly.

## Scope
This spec covers five commands for day-to-day use across code, documentation, notes, and repo organization:

- `/diff`
- `/flow`
- `/sync-doc`
- `/debug`
- `/repo-wtf`

The commands are assistance tools, not autonomous executors.

## Principles
- Prefer short, actionable output.
- Separate facts from hypotheses.
- Do not modify files unless explicitly requested.
- Prefer diagnosis before action.
- Keep outputs useful for code, docs, notes, and organization.

## Command definitions

### `/diff`
**Purpose:** Review changes before editing, committing, or opening a PR.

**Inputs:**
- current diff
- staged changes
- unstaged changes
- branch comparison

**Outputs:**
- short summary
- risk notes
- possible regressions
- files that need extra care
- recommendation: proceed, adjust, or stop

**Limits:**
- does not edit files
- does not assume intent beyond visible changes

### `/flow`
**Purpose:** Trace a code or data flow from one point to another.

**Inputs:**
- source symbol or event
- target symbol, file, or behavior

**Outputs:**
- main path
- key symbols involved
- decision points
- dependency chain
- fragile spots

**Limits:**
- must distinguish static flow from dynamic or inferred flow
- if multiple paths exist, rank them by relevance

### `/sync-doc`
**Purpose:** Find mismatches between code and documentation or notes.

**Inputs:**
- file paths
- folder scope
- topic

**Outputs:**
- missing documentation
- outdated documentation
- contradictions
- likely priority order

**Limits:**
- does not rewrite docs unless asked
- separates “missing” from “incorrect”

### `/debug`
**Purpose:** Investigate bugs with a structured method.

**Inputs:**
- symptom
- logs
- stack traces
- reproduction steps

**Outputs:**
- hypotheses
- supporting and opposing evidence
- most likely cause
- reproduction guidance
- fix suggestion
- verification steps

**Limits:**
- do not jump straight to fixes
- call out uncertainty explicitly

### `/repo-wtf`
**Purpose:** Explain confusing or surprising repo structure and behavior.

**Inputs:**
- symbol
- module
- file
- free-form question

**Outputs:**
- plain-language explanation
- local architecture summary
- dependencies
- risks of changing it
- relevant entry points

**Limits:**
- prioritizes understanding over search results
- should explain “why it exists like this” when possible

## Intended usage order
1. `/diff` to review change risk.
2. `/flow` to understand impact and control flow.
3. `/sync-doc` to keep notes/docs aligned.
4. `/debug` when behavior is wrong.
5. `/repo-wtf` when the repo makes no sense.

## Non-goals
- full automation of refactors
- automatic file writes
- replacing code review
- replacing test execution

## Success criteria
- faster day-to-day understanding
- fewer unsafe edits
- better alignment between code and docs
- clearer debugging workflow
- less time spent re-deriving repo structure
