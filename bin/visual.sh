#!/usr/bin/env bash
#
# This file is part of the adminata package.
#
# (c) IDCT Bartosz Pachołek <bartosz@idct.tech>
#
# Forked from the Sonata Project
# (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
#
# For the full copyright and license information, please view the LICENSE
# file that was distributed with this source code.

# Runs a Playwright command against the demo application.
#
# The browsers run inside mcr.microsoft.com/playwright:$PLAYWRIGHT_VERSION-noble, the image the
# `visual` workflow uses, because the committed screenshots under tests-adminata/Visual/__snapshots__ are
# pixels: the same page rendered by the same browser version with different fonts is a different
# image. The demo itself is served by the host's PHP, which the container reaches over the host
# network.
#
# Set ADMINATA_PLAYWRIGHT_LOCAL=1 to skip the container and use browsers installed on this machine
# (`npx playwright install`). Useful while writing a spec; its screenshots will not match the
# committed baselines.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PORT="${ADMINATA_DEMO_PORT:-8000}"
URL="http://127.0.0.1:${PORT}"
PLAYWRIGHT_VERSION="$(node -p "require('${ROOT}/package.json').devDependencies['@playwright/test']")"

cd "$ROOT"

server=""

cleanup() {
    if [ -n "$server" ]; then
        kill "$server" 2>/dev/null || true
        wait "$server" 2>/dev/null || true
    fi
}
trap cleanup EXIT

# `--user`, because the demo's firewall is in-memory http_basic and an anonymous request is a
# redirect — which is still an answer, and would look like a server that is already up.
#
# The body is captured rather than piped into `grep -q`: under `pipefail` a quiet grep closes the
# pipe on its first match, curl dies of SIGPIPE, and the pipeline reports failure for the one case
# that was a success.
demo_is_up() {
    local body
    body="$(curl --silent --max-time 10 --user admin:admin "${URL}/admin/tests/app/product/list" || true)"

    [[ "$body" == *adminata-list* ]]
}

if ! demo_is_up; then
    if curl --silent --output /dev/null --max-time 2 "${URL}"; then
        # Something else has the port. Running the suite against it would report an application
        # adminata does not ship — every page a 404, and the findings ledger nonsense.
        echo "Port ${PORT} is serving something that is not the demo." >&2
        echo "Set ADMINATA_DEMO_PORT to a free port, or stop what is listening." >&2
        exit 1
    fi

    php -S "127.0.0.1:${PORT}" -t tests-adminata/App/public >/dev/null 2>&1 &
    server=$!

    for _ in $(seq 1 30); do
        if demo_is_up; then
            break
        fi
        sleep 0.5
    done

    if ! demo_is_up; then
        echo "The demo did not come up on ${URL}; run \`make demo-db demo-assets\` first." >&2
        exit 1
    fi
fi

if [ -n "${ADMINATA_PLAYWRIGHT_LOCAL:-}" ]; then
    ADMINATA_DEMO_URL="$URL" "$@"
    exit $?
fi

docker run --rm --network host \
    --volume "${ROOT}:/work" \
    --workdir /work \
    --user "$(id -u):$(id -g)" \
    --env HOME=/tmp \
    --env ADMINATA_DEMO_URL="$URL" \
    --env CI="${CI:-}" \
    "mcr.microsoft.com/playwright:v${PLAYWRIGHT_VERSION}-noble" \
    "$@"
