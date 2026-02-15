# Gemogen — Progress Tracker

## Current Version: 0.1.0-dev

## Milestones

| # | Milestone | Status | Commit | Date |
|---|-----------|--------|--------|------|
| M0 | Project Scaffolding | Done | `ab962fa` | 2026-02-07 |
| M1 | Core Engine | Done | `acfa209` | 2026-02-07 |
| M1.5 | Content Source System | Done | `73972fe` | 2026-02-07 |
| M2 | WP-CLI Integration | Done | `92c3292` | 2026-02-07 |
| M3 | Content Tagging & Run History | Done | `23e2583` | 2026-02-10 |
| M4 | Extension System | Pending | — | — |
| M5 | REST API | Done | `90f97a4` | 2026-02-15 |
| M6 | React Admin UI (simplified) | Pending (blocked by M5) | — | — |
| M7 | Testing & Quality | Pending (blocked by all) | — | — |

## Git History

```
90f97a4 feat: add REST API endpoints (Milestone 5)
23e2583 feat: add content tagging and run history (Milestone 3)
92c3292 feat: add WP-CLI commands (Milestone 2)
73972fe feat: add content source system (Milestone 1.5)
acfa209 feat: implement core engine with scenario system (Milestone 1)
d6dc77b docs: add project README
ab962fa feat: scaffold Gemogen plugin (Milestone 0)
```

## What's Built

### M0: Project Scaffolding
- `gemogen.php` — Plugin entry point
- `composer.json` — PSR-4 autoloading, PHPUnit 10
- `package.json` — @wordpress/scripts, @wordpress/env
- `.wp-env.json` — Docker test environment
- `phpunit.xml.dist` — Unit + integration suites
- `src/Plugin.php` — Boot sequence with DI container wiring
- `src/Container.php` — Lightweight DI (set/get/has, singleton)
- `tests/bootstrap.php` — WP test suite loader
- `tests/Unit/Core/ContainerTest.php` — 7 tests

### M1: Core Engine
- `src/Contracts/ScenarioInterface.php` — Central scenario contract
- `src/Contracts/GeneratorInterface.php` — Generator contract
- `src/Core/AbstractScenario.php` — Base scenario with validation + defaults
- `src/Core/ScenarioManager.php` — Registry, execution, rollback, hooks
- `src/Core/Logger.php` — Logs to error_log or WP_CLI
- `src/Generators/PostGenerator.php` — Posts/pages/CPTs
- `src/Generators/UserGenerator.php` — Users with roles
- `src/Generators/TaxonomyGenerator.php` — Categories/tags/custom terms
- `src/Generators/CommentGenerator.php` — Comments on posts
- `src/Generators/MediaGenerator.php` — Placeholder PNG images
- `src/Scenarios/CoreContentScenario.php` — Orchestrates all generators
- `tests/Unit/Core/ScenarioManagerTest.php` — 8 tests
- `tests/Unit/Core/AbstractScenarioTest.php` — 6 tests

### M3: Content Tagging & Run History
- `src/Core/RunHistory.php` — Stores last 20 runs in WP option (FIFO)
- Modified `src/Generators/PostGenerator.php` — Adds `_gemogen_generated` post meta
- Modified `src/Generators/UserGenerator.php` — Adds `_gemogen_generated` user meta
- Modified `src/Generators/TaxonomyGenerator.php` — Adds `_gemogen_generated` term meta
- Modified `src/Generators/CommentGenerator.php` — Adds `_gemogen_generated` comment meta
- Modified `src/Generators/MediaGenerator.php` — Adds `_gemogen_generated` post meta
- Modified `src/Core/ScenarioManager.php` — Records runs in RunHistory after execute()
- Modified `src/CLI/ScenarioCommand.php` — Added `rollback --last`, `history`, `reset` commands
- Modified `src/Plugin.php` — Wired RunHistory into DI container
- `tests/Unit/Core/RunHistoryTest.php` — 6 tests

### M5: REST API
- `src/REST/BaseController.php` — Base REST controller with `gemogen/v1` namespace, `manage_options` permission check (returns `WP_Error` on failure)
- `src/REST/ScenarioController.php` — Full REST controller with 7 routes:
  - `GET /scenarios` — List all scenarios with schemas
  - `GET /scenarios/<id>` — Single scenario details (404 on missing)
  - `POST /scenarios/<id>/execute` — Run with config body, returns 201 with created_ids
  - `POST /scenarios/<id>/rollback` — Rollback with created_ids body, validates input
  - `GET /history` — Recent run history (newest first, uses `wp_date`)
  - `POST /reset` — Remove all `_gemogen_generated` content (mirrors CLI reset)
  - `GET /status` — Plugin version, scenario count, total runs, last run
- Modified `src/Plugin.php` — Wired ScenarioController on `rest_api_init` hook
- `tests/Unit/REST/wp-stubs.php` — Minimal WP REST class stubs for unit testing
- `tests/Unit/REST/ScenarioControllerTest.php` — 20 tests

## Test Results

| Suite | Tests | Assertions | Status |
|-------|-------|-----------|--------|
| Unit | 66 | 131 | All passing |
| Integration | — | — | Not yet (needs wp-env) |

## Decisions Log

| Date | Decision | Rationale |
|------|----------|-----------|
| 2026-02-07 | PHPUnit 10+ (no Brain\Monkey) | Using real WP test suite instead of mocking |
| 2026-02-07 | @wordpress/env for test env | Docker-based, isolated, CI-friendly |
| 2026-02-07 | Lightweight custom Container | No Symfony DI — keep it simple |
| 2026-02-07 | `function_exists` guards on hooks | Allows unit tests without WP loaded |
| 2026-02-07 | Custom content sources (CSV/JSON + templates) | Users want their own content, not just lorem ipsum |
| 2026-02-07 | ~~Two-phase editing~~ → Simple preview | Ideator review: too much UI complexity for a dev tool. Show preview of what will be generated, no per-item editing |
| 2026-02-07 | ~~Progressive field disclosure~~ → Deferred | Ideator review: defer advanced field-level control to a later version |
| 2026-02-10 | Content tagging (`_gemogen_generated`) | Tag all generated content for tracking and bulk cleanup |
| 2026-02-10 | Run history + `rollback --last` | Store runs in WP option, enable rollback without manual ID tracking |
| 2026-02-10 | Extensions before Admin UI | Ideator review: extensibility is the core value prop, ship it before the UI |
| 2026-02-10 | Simplified Admin UI | Drop ContentManager, FieldEditor, ContentPreview, FileImporter. Ship: scenario list + run + results + history |
| 2026-02-10 | Presets/Profiles | Named saved configs for scenarios (`--preset=my-blog`) |
| 2026-02-10 | JSON instead of YAML for scenarios | No extra dependency needed — JSON is native to PHP |

## Next Steps

1. **M4: Extension System** — JSON scenario definitions, presets/profiles, DynamicScenario, WooCommerce scenario
2. **M6: React Admin UI (simplified)** — Now unblocked (M5 done)
3. **M7: Testing & Quality** — Final polish

### Tech Debt (from M5 review)
- REST rollback does not update run history (CLI does) — behavioral inconsistency (m4)
- Reset logic duplicated between CLI and REST (~50 lines) — extract to shared service (m5)
- CLI `history` uses `date()` while REST uses `wp_date()` — minor inconsistency
