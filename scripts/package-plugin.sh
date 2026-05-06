#!/usr/bin/env bash
set -euo pipefail

PLUGIN_SLUG="wp-login-for-ai"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PLUGIN_DIR="${ROOT_DIR}/${PLUGIN_SLUG}"
DIST_DIR="${ROOT_DIR}/dist"
STAGING_DIR="${DIST_DIR}/package"
STAGING_PLUGIN_DIR="${STAGING_DIR}/${PLUGIN_SLUG}"
ZIP_PATH="${DIST_DIR}/${PLUGIN_SLUG}.zip"

fail() {
    printf 'Error: %s\n' "$*" >&2
    exit 1
}

command -v zip >/dev/null 2>&1 || fail "zip is required to package the plugin."

[[ -d "${PLUGIN_DIR}" ]] || fail "Plugin directory not found: ${PLUGIN_DIR}"
[[ -f "${PLUGIN_DIR}/${PLUGIN_SLUG}.php" ]] || fail "Plugin entry file not found: ${PLUGIN_DIR}/${PLUGIN_SLUG}.php"

rm -rf "${STAGING_DIR}" "${ZIP_PATH}"
mkdir -p "${STAGING_DIR}" "${DIST_DIR}"

cp -R "${PLUGIN_DIR}" "${STAGING_PLUGIN_DIR}"

rm -rf \
    "${STAGING_PLUGIN_DIR}/vendor" \
    "${STAGING_PLUGIN_DIR}/tests" \
    "${STAGING_PLUGIN_DIR}/composer.json" \
    "${STAGING_PLUGIN_DIR}/composer.lock"

find "${STAGING_PLUGIN_DIR}" -name '.DS_Store' -delete
find "${STAGING_PLUGIN_DIR}" -name 'Thumbs.db' -delete

if [[ -f "${ROOT_DIR}/README.md" ]]; then
    cp "${ROOT_DIR}/README.md" "${STAGING_PLUGIN_DIR}/README.md"
fi

(
    cd "${STAGING_DIR}"
    zip -qr "${ZIP_PATH}" "${PLUGIN_SLUG}"
)

rm -rf "${STAGING_DIR}"

printf 'Created %s\n' "${ZIP_PATH}"
