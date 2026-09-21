#!/usr/bin/env bash
set -euo pipefail
: "${JOOMLA_ROOT:?Set an empty directory for the pinned Joomla distribution}"
[[ ! -e "$JOOMLA_ROOT" ]]
work="$(mktemp -d)"
trap 'rm -rf -- "$work"' EXIT
curl --fail --location --proto '=https' --tlsv1.2 --retry 3 --connect-timeout 30 --max-time 180 \
  https://github.com/joomla/joomla-cms/releases/download/6.1.3/Joomla_6.1.3-Stable-Full_Package.tar.gz \
  --output "$work/joomla.tar.gz"
printf '%s  %s\n' '184f8c582cde5981693de7c28547c6e834c48c50cb377c7b8421bbfd33bbdf6f' "$work/joomla.tar.gz" | sha256sum --check
mkdir -p -- "$JOOMLA_ROOT"
tar --no-same-owner -xzf "$work/joomla.tar.gz" -C "$JOOMLA_ROOT"
