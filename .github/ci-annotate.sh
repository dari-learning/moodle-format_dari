#!/bin/bash
# Temporary CI debugging: run a check and, if it fails, publish its output tail as an annotation.
name="$1"; shift
out="$(mktemp)"
set -o pipefail
"$@" 2>&1 | tee "$out"
status=$?
if [ $status -ne 0 ]; then
  grep -v '^\s*$' "$out" | tail -c 12000 > "$out.tail"
  part=0
  while [ -s "$out.tail" ] && [ $part -lt 4 ]; do
    chunk="$(head -c 3000 "$out.tail")"
    tail -c +3001 "$out.tail" > "$out.rest"; mv "$out.rest" "$out.tail"
    msg="$(printf '%s' "$chunk" | sed ':a;N;$!ba;s/%/%25/g;s/\r/%0D/g;s/\n/%0A/g')"
    echo "::error title=${name} part ${part}::${msg}"
    part=$((part+1))
  done
fi
exit $status
