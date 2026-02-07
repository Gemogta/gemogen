# Gemogen

Scenario-based content generator for WordPress development.

## What is Gemogen?

Setting up a local WordPress site for development is slow. Every time you need to test a plugin (LearnDash, WooCommerce, ACF...), you have to manually create posts, pages, users, products, courses through the UI.

Gemogen fixes this. Define what you need ("a WooCommerce store with 50 products" or "a LearnDash course with 5 lessons and 3 enrolled students") and Gemogen creates everything programmatically.

## Features

- **Scenario system** — Pre-built and custom content generation scenarios
- **3 execution modes** — WP-CLI, Composer commands, React admin panel
- **Extendable** — Register custom scenarios via hooks or YAML/JSON files
- **Rollback** — Undo any generated content with one command
- **Dev-only** — Built for local development and testing

## Requirements

- PHP 8.1+
- WordPress 6.0+
- Composer
- Node.js (for admin UI build)
- Docker (for test environment via `@wordpress/env`)

## Installation

```bash
cd wp-content/plugins/gemogen
composer install
npm install
npm run build
```

Activate the plugin in WordPress admin or via WP-CLI:

```bash
wp plugin activate gemogen
```

## Usage

### WP-CLI

```bash
# List available scenarios
wp gemogen list

# Run a scenario
wp gemogen run core-content --config='{"posts": 10, "users": 3}'

# Rollback generated content
wp gemogen rollback core-content --ids='[1,2,3,4,5]'

# View scenario details
wp gemogen info core-content
```

### Composer

```bash
composer gemogen:list
composer gemogen:run -- core-content
```

### Admin UI

Navigate to **Tools > Gemogen** in the WordPress admin. Browse scenarios, configure options, and run them with a single click.

## Extending Gemogen

### Register a custom scenario via hook

```php
add_action( 'gemogen_register_scenarios', function ( $manager ) {
    $manager->register( new MyCustomScenario() );
} );
```

### YAML scenario definition

Place YAML files in `wp-content/gemogen-scenarios/`:

```yaml
id: learndash-course
name: LearnDash Course Setup
description: Creates a course with lessons and enrolled students
requires: [sfwd-lms/sfwd_lms.php]
steps:
  - generator: post
    params: { post_type: sfwd-courses, count: 1 }
    as: course
  - generator: post
    params: { post_type: sfwd-lessons, count: 5, post_parent: "@course" }
    as: lessons
  - generator: user
    params: { role: subscriber, count: 3 }
    as: students
```

## Development

### Run tests

```bash
# Start the Docker test environment
npm run env:start

# Run all tests
npm run env:test

# Run unit tests only
composer test:unit
```

### Build the admin UI

```bash
npm run dev    # Development (watch mode)
npm run build  # Production build
```

## License

GPL-2.0-or-later
