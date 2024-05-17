#!/usr/bin/env bash

# @file
# Place this in ./bin/perms to create a configured controller for aklump/easy-perms

s="${BASH_SOURCE[0]}";[[ "$s" ]] || s="${(%):-%N}";while [ -h "$s" ];do d="$(cd -P
"$(dirname "$s")" && pwd)";s="$(readlink "$s")";[[ $s != /* ]] &&
s="$d/$s";done;__DIR__=$(cd -P "$(dirname "$s")" && pwd)

chmod u+x "$__DIR__/../vendor/bin/easy-perms"
"$__DIR__/../vendor/bin/easy-perms" "$__DIR__/config/perms.yml" "$@"
