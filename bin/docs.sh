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

# Builds the documentation with the pinned Sphinx of docs/requirements.txt.
#
# A local `sphinx-build` is used when there is one; otherwise the build runs in a Python container,
# so the only thing a contributor needs installed is Docker — the same bargain bin/visual.sh makes
# for the browsers.
#
# Warnings are errors (-W): a cross-reference that stops resolving when a page is pruned fails the
# build rather than shipping a dead link.

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
OUT="${1:-${ROOT}/var/docs}"

cd "$ROOT"

if command -v sphinx-build >/dev/null 2>&1; then
    exec sphinx-build -W --keep-going -b html docs "$OUT"
fi

PYTHON_IMAGE="python:3.13-slim"

echo "No local sphinx-build; using ${PYTHON_IMAGE}." >&2

exec docker run --rm \
    --volume "${ROOT}:/work" \
    --workdir /work \
    --user "$(id -u):$(id -g)" \
    --env HOME=/tmp \
    --env PIP_ROOT_USER_ACTION=ignore \
    "$PYTHON_IMAGE" \
    sh -c 'pip install --quiet --disable-pip-version-check --no-warn-script-location -r docs/requirements.txt \
        && python -m sphinx -W --keep-going -b html docs '"${OUT/#$ROOT/\/work}"
