#!/usr/bin/env bash
#
# Reports what changed upstream between two tags of one forked package, split into the part
# upstream/sync.sh can apply mechanically and the user-interface part adminata re-implements by
# hand. Paste the output into the sync issue.
#
# This reads the upstream refs only, so it also works for the trees in upstream/merged.txt, whose
# sources were folded into another package directory. For those, nothing is applied mechanically —
# the report is the whole tool — and the banner below says so.
#
#   upstream/diff.sh <package> <from-tag> <to-tag>
#   upstream/diff.sh twig-extensions 2.5.0 2.6.0

set -euo pipefail

if [ $# -ne 3 ]; then
    echo "usage: $0 <package> <from-tag> <to-tag>" >&2
    exit 64
fi

package=$1
from=$2
to=$3
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
remote="upstream-$package"
merged_file="$root/upstream/merged.txt"
exclude_file="$root/upstream/exclude/$package.txt"

merged_into=$(grep -v '^#' "$merged_file" | awk -v p="$package" '$1 == p { print $2 }')

if [ ! -f "$exclude_file" ]; then
    echo "no exclusion list at $exclude_file" >&2
    exit 66
fi

if ! git -C "$root" remote get-url "$remote" > /dev/null 2>&1; then
    url=$(grep -v '^#' "$root/upstream/remotes.txt" | awk -v p="$package" '$1 == p { print $2 }')
    if [ -z "$url" ]; then
        echo "unknown package $package (not in upstream/remotes.txt)" >&2
        exit 66
    fi
    git -C "$root" remote add "$remote" "$url"
fi

for tag in "$from" "$to"; do
    git -C "$root" fetch --quiet --no-tags "$remote" "+refs/tags/$tag:refs/upstream/$package/$tag"
done

owned=()
while read -r line; do
    case "$line" in ''|'#'*) continue ;; esac
    owned+=(":(exclude,glob)$line")
done < "$exclude_file"

range="refs/upstream/$package/$from..refs/upstream/$package/$to"

echo "### $package $from → $to"
echo

if [ -n "$merged_into" ]; then
    echo "> \`$package\` was merged into \`$merged_into\` and has no tree of its own."
    echo "> \`upstream/sync.sh\` refuses it: everything below is ported by hand, and the split into"
    echo "> \"applies mechanically\" and \"owned by adminata\" is only a hint about where to look."
    echo
fi

echo "#### PHP and configuration (upstream/sync.sh applies this)"
echo
git -C "$root" diff --stat "$range" -- . "${owned[@]}"
echo
echo "#### Owned by adminata — re-implement by hand"
echo
git -C "$root" diff --stat "$range" -- "${owned[@]/:(exclude,glob)/}" || true
echo
echo "#### Commits"
echo
git -C "$root" log --oneline --no-merges "$range"
