<?php

namespace BetterReflection\Reflector;

interface Reflector
{
    public function reflect($symbolName);

    public function getAllSymbols();
}
