#!/usr/bin/env bash
#
# Applies the PHP-side changes of one upstream release onto this repository, skipping every path
# adminata owns (upstream/exclude/<package>.txt). Upstream speaks the Sonata names and this
# repository does not (PLAN/v2 N16), so nothing is applied as a patch: for every file the release
# touched, both upstream versions are translated through upstream/rename/apply.php — base (the
# tag we sit at) and head (the tag we move to) — normalised by php-cs-fixer so that the rename's
# reordering of the imports does not conflict on every file, and merged three-way onto ours with
# `git merge-file`. Conflict markers are left for the hand; the user-interface changes the
# exclusion list skipped are re-implemented by hand.
#
# Only `admin-bundle` can be synced, and it is the repository itself: upstream's src/ and tests/
# are this repository's src/ and tests/, so paths need no directory prefix — only the rename.
#
#   upstream/sync.sh <package> <to-tag>
#   upstream/sync.sh admin-bundle 4.44.0
#
# Afterwards: `make cs-fix rector-fix phpstan test check-names`, one commit "Sync <package>
# <to-tag>", then a commit bumping the row in upstream/remotes.txt and UPSTREAM.md.
#
# Trees listed in upstream/merged.txt are part of the admin bundle now and are refused here; their
# upstream releases are ported by hand, through the same translation (upstream/diff.sh).

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
merged_file="$root/upstream/merged.txt"
exclude_file="$root/upstream/exclude/$package.txt"

from=$(grep -v '^#' "$remotes_file" | awk -v p="$package" '$1 == p { print $3 }')
url=$(grep -v '^#' "$remotes_file" | awk -v p="$package" '$1 == p { print $2 }')

if [ -z "$from" ] || [ -z "$url" ]; then
    echo "unknown package $package (not in upstream/remotes.txt)" >&2
    exit 66
fi

# A merged tree has no tree of its own to apply a patch to; applying it at the root would
# resurrect sources that were deliberately folded into the admin bundle under other names, so
# refuse before anything is fetched or written.
merged_into=$(grep -v '^#' "$merged_file" | awk -v p="$package" '$1 == p { print $2 }')

if [ -n "$merged_into" ]; then
    cat >&2 <<ERR
$package was merged into $merged_into and no longer has a tree of its own, so an upstream release
cannot be replayed onto it mechanically.

Report what changed and port it by hand:
  upstream/diff.sh $package $from $to

Then bump the row in upstream/remotes.txt and in UPSTREAM.md by hand — a merged package has no
\`replace\` entry to bump. See upstream/merged.txt.
ERR
    exit 67
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
engine="$root/upstream/rename/apply.php"
fixer="$root/vendor/bin/php-cs-fixer"
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT

# Translates one upstream blob into this repository's names and formatting, into $work.
translate() { # <ref> <upstream path> <our path> <output file>
    if git -C "$root" cat-file -e "$1:$2" 2>/dev/null; then
        git -C "$root" show "$1:$2" | php "$engine" --stdin --as "$3" > "$4"
        case "$3" in
            *.php)
                cp "$4" "$work/fix.php"
                "$fixer" fix --quiet --config="$root/.php-cs-fixer.dist.php" --allow-risky=yes "$work/fix.php" > /dev/null 2>&1 || true
                cp "$work/fix.php" "$4"
                ;;
        esac
    else
        : > "$4"
    fi
}

applied=0
conflicts=0
while IFS=$'\t' read -r status upstream_path rest; do
    [ -z "$status" ] && continue
    case "$status" in
        R*) new_upstream_path=$rest ;;
        *) new_upstream_path=$upstream_path ;;
    esac
    ours=$(php "$engine" --path "$upstream_path")
    theirs=$(php "$engine" --path "$new_upstream_path")

    case "$status" in
        D)
            git -C "$root" rm --quiet -- "$ours" 2>/dev/null || true
            ;;
        A)
            mkdir -p "$root/$(dirname "$theirs")"
            translate "refs/upstream/$package/$to" "$new_upstream_path" "$theirs" "$root/$theirs"
            git -C "$root" add -- "$theirs"
            ;;
        *)
            if [ "$ours" != "$theirs" ] && [ -f "$root/$ours" ]; then
                git -C "$root" mv -- "$ours" "$theirs"
            fi
            translate "refs/upstream/$package/$from" "$upstream_path" "$theirs" "$work/base"
            translate "refs/upstream/$package/$to" "$new_upstream_path" "$theirs" "$work/head"
            if [ ! -f "$root/$theirs" ]; then
                cp "$work/head" "$root/$theirs"
                git -C "$root" add -- "$theirs"
            elif ! git merge-file -L ours -L "upstream $from" -L "upstream $to" "$root/$theirs" "$work/base" "$work/head"; then
                conflicts=$((conflicts + 1))
                echo "conflict: $theirs"
            fi
            ;;
    esac
    applied=$((applied + 1))
done < <(git -C "$root" diff --name-status -M "$range" -- . "${owned[@]}")

if [ "$applied" -eq 0 ]; then
    echo "nothing to apply for $package $from → $to outside the paths adminata owns"
fi

sed -i "s|^\\($package[[:space:]]\\+$url[[:space:]]\\+\\)$from\$|\\1$to|" "$remotes_file"

cat <<MSG

Applied $package $from → $to: $applied files, $conflicts with conflict markers.

Next:
  1. resolve the conflict markers, if any
  2. re-implement the user-interface changes: upstream/diff.sh $package $from $to
  3. make cs-fix rector-fix phpstan test check-names
  4. commit "Sync $package $to"
  5. bump the row in UPSTREAM.md (upstream/remotes.txt is done), and add a CHANGELOG.md line for
     anything ported by hand
MSG
