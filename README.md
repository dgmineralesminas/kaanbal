# --------------------------------------------------

# Dependencies

# --------------------------------------------------

/vendor/
/node_modules/

# --------------------------------------------------

# Composer

# --------------------------------------------------

composer.phar
.phpunit.result.cache

# Keep composer.lock for reproducible builds.

# Do NOT ignore composer.lock.

# --------------------------------------------------

# PHPUnit / Testing

# --------------------------------------------------

.phpunit.cache/
.phpunit.result.cache
coverage/
build/
tests/tmp/
tests/cache/

# WordPress test suite local files

/tmp/
/wordpress-tests-lib/
/wordpress-develop/

# --------------------------------------------------

# Static Analysis / Code Quality

# --------------------------------------------------

.phpstan/
.phpstan-cache/
.phpcs-cache
.phpcs-cache*

# --------------------------------------------------

# Environment / Secrets

# --------------------------------------------------

.env
.env.*
!.env.example

auth.json

# --------------------------------------------------

# WordPress Local Runtime

# --------------------------------------------------

wp-config.php
wp-config-local.php

# This repository should only contain the plugin.

# Ignore accidental WordPress Core copies if created locally.

/wp-admin/
/wp-includes/
/wp-content/

# --------------------------------------------------

# Uploads / Runtime files

# --------------------------------------------------

uploads/
cache/
logs/
*.log

# --------------------------------------------------

# Generated / Temporary

# --------------------------------------------------

*.tmp
*.temp
*.bak
*.swp
*.swo
*.orig

.DS_Store
Thumbs.db
desktop.ini

# --------------------------------------------------

# IDE / Editors

# --------------------------------------------------

.idea/
.vscode/
*.code-workspace

# Keep shared editor configuration only if intentionally committed.

# Remove .vscode/ above if the project later needs shared VS Code settings.

# --------------------------------------------------

# Operating System

# --------------------------------------------------

.AppleDouble
.LSOverride
._*

# --------------------------------------------------

# Local tooling

# --------------------------------------------------

.tooling/
.cache/

# --------------------------------------------------

# AI / Agent local artifacts

# --------------------------------------------------

# Do not ignore docs/specs or audit reports.

# They are part of the repository history.

# Ignore only temporary agent scratch files if generated locally.

.agent-tmp/
.ai-tmp/
.codex-tmp/

# --------------------------------------------------

# Archives / Packages

# --------------------------------------------------

*.zip
*.tar
*.tar.gz
*.tgz

# --------------------------------------------------

# Certificates / Keys / Secrets

# --------------------------------------------------

*.pem
*.key
*.p12
*.pfx

# --------------------------------------------------

# Local database dumps

# --------------------------------------------------

*.sql
*.sql.gz

# --------------------------------------------------

# Misc

# --------------------------------------------------

*.pid
*.seed
