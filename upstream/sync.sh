#!/usr/bin/env bash
#
# Applies the PHP-side changes of one upstream release onto packages/<package>/, skipping every
# path adminata owns (upstream/exclude/<package>.txt). What it cannot apply lands in .rej files
# for you to resolve; the user-interface changes it skipped are re-implemented by hand.
#
#   upstream/sync.sh <package> <to-tag>
#   upstream/sync.sh twig-extensions 2.7.0
#
# Afterwards: `make cs-fix rector-fix phpstan test`, one commit "Sync <package> <to-tag>", then a
# commit bumping the `replace` entry in composer.json and the row in UPSTREAM.md.

set -euo pipefail

if [ $# -ne 2 ]; then
    echo "usage: $0 <package> <to-tag>" >&2
    exit 64
fi

package=$1
to=$2
root=$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)
remote="upstream-$package"
remotes_file="$root/upstream/remotes.txt"
exclude_file="$root/upstream/exclude/$package.txt"

from=$(grep -v '^#' "$remotes_file" | awk -v p="$package" '$1 == p { print $3 }')
url=$(grep -v '^#' "$remotes_file" | awk -v p="$package" '$1 == p { print $2 }')

if [ -z "$from" ] || [ -z "$url" ]; then
    echo "unknown package $package (not in upstream/remotes.txt)" >&2
    exit 66
fi

if [ "$from" = "$to" ]; then
    echo "$package is already at $to" >&2
    exit 0
fi

if [ -n "$(git -C "$root" status --porcelain)" ]; then
    echo "working tree is not clean; commit or stash first" >&2
    exit 65
fi

git -C "$root" remote get-url "$remote" > /dev/null 2>&1 || git -C "$root" remote add "$remote" "$url"

for tag in "$from" "$to"; do
    git -C "$root" fetch --quiet --no-tags "$remote" "+refs/tags/$tag:refs/upstream/$package/$tag"
done

owned=()
while read -r line; do
    case "$line" in ''|'#'*) continue ;; esac
    owned+=(":(exclude,glob)$line")
done < "$exclude_file"

range="refs/upstream/$package/$from..refs/upstream/$package/$to"
patch=$(mktemp)
trap 'rm -f "$patch"' EXIT

git -C "$root" diff "$range" -- . "${owned[@]}" > "$patch"

if [ ! -s "$patch" ]; then
    echo "nothing to apply for $package $from → $to outside the paths adminata owns"
else
    git -C "$root" apply -3 --directory="packages/$package" "$patch"
fi

sed -i "s|^\\($package[[:space:]]\\+$url[[:space:]]\\+\\)$from\$|\\1$to|" "$remotes_file"

cat <<MSG

Applied $package $from → $to.

Next:
  1. resolve any *.rej / conflict markers
  2. re-implement the user-interface changes: upstream/diff.sh $package $from $to
  3. make cs-fix rector-fix phpstan test
  4. commit "Sync $package $to"
  5. bump the \`replace\` entry in composer.json and the row in UPSTREAM.md, and add a
     CHANGELOG.md line for anything ported by hand
MSG
