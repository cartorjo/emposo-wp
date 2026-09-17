#!/usr/bin/env bash
# Run a PHP/Composer command inside a pinned container.
#
# This machine has no host PHP, Composer or PHPCS, and installing them via
# Homebrew risks a host/container version mismatch. Everything PHP therefore
# runs in php:8.3-cli, matching .wp-env.json's phpVersion so PHPCS and PHPStan
# analyse under the same PHP the site runs on.
set -euo pipefail

PHP_IMAGE="${PHP_IMAGE:-php:8.3-cli}"
REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# A named volume keeps Composer's cache warm across runs.
# -t only when a TTY exists: `docker run -it` fails in CI and when piped.
TTY_FLAG=()
if [ -t 0 ] && [ -t 1 ]; then TTY_FLAG=(-t); fi

docker run --rm -i "${TTY_FLAG[@]}" \
	-v "${REPO_ROOT}:/app" \
	-v emposo-composer-cache:/tmp/composer-cache \
	-e COMPOSER_CACHE_DIR=/tmp/composer-cache \
	-e COMPOSER_ALLOW_SUPERUSER=1 \
	-w /app \
	--entrypoint /bin/sh \
	"${PHP_IMAGE}" -c '
		set -e
		if ! command -v composer >/dev/null 2>&1; then
			# unzip lets Composer use cached zips instead of re-cloning every package
			apt-get update -qq >/dev/null 2>&1
			apt-get install -y -qq --no-install-recommends unzip git >/dev/null 2>&1
			curl -sS https://getcomposer.org/installer | php -- \
				--install-dir=/usr/local/bin --filename=composer --quiet
		fi
		# Raise the limit via conf.d, not `php -d`: PHPStan runs its analysis in
		# parallel worker processes, and a -d flag on the parent does not reach
		# them — they inherit php.ini and die at the 128M default. An ini file
		# applies to every PHP process in the container, so the limit holds
		# however phpstan is invoked (with or without --memory-limit).
		mkdir -p "${PHP_INI_DIR}/conf.d"
		echo "memory_limit = 3G" > "${PHP_INI_DIR}/conf.d/zz-emposo.ini"
		export PATH="/app/vendor/bin:${PATH}"
		exec "$@"
	' -- "$@"
