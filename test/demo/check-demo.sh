#!/usr/bin/env bash

cd "$(dirname ${BASH_SOURCE[0]})"
cd ../..

check () {
  OUT=`php $1`
  if [ "$OUT" != "$2" ]
  then
    echo "failed: $1"
    exit 1
  else
    echo "ok: $1"
  fi
}

check demo/basic-reflection/example1.php $'stdClass\ninternal'
check demo/basic-reflection/example2.php $'Roave\BetterReflection\Reflection\ReflectionClass\nnot internal'
check demo/basic-reflection/example3.php $'MyClass\nprivate\nstring\nstring'
check demo/monkey-patching/index.php $'4'
check demo/new-instance/index.php $'4'
