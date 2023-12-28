#!/bin/bash
# vim:sw=4:ts=4:et

set -e

source /lib.sh

mkdir -p /docker-entrypoint.d/

if /usr/bin/find "/docker-entrypoint.d/" -mindepth 1 -maxdepth 1 -type f -print -quit 2>/dev/null | read v; then
	entrypoint_log "/docker-entrypoint.d/ is not empty, will attempt to perform configuration"

	entrypoint_log "looking for shell scripts in /docker-entrypoint.d/"
	find "/docker-entrypoint.d/" -follow -type f -print | sort -V | while read -r f; do
		case "$f" in
			*.envsh)
				if [ -x "$f" ]; then
					entrypoint_log "Sourcing $f";
					. "$f"
				else
					# warn on shell scripts without exec bit
					entrypoint_log "Ignoring $f, not executable";
				fi
				;;

			*.sh)
				if [ -x "$f" ]; then
					entrypoint_log "Launching $f";
					"$f"
				else
					# warn on shell scripts without exec bit
					entrypoint_log "Ignoring $f, not executable";
				fi
				;;

			*) entrypoint_log "Ignoring $f";;
		esac
	done

	entrypoint_log "Configuration complete; ready for start up"
else
	entrypoint_log "No files found in /docker-entrypoint.d/, skipping configuration"
fi

exec "$@"
