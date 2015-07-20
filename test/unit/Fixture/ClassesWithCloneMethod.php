<?php

namespace BetterReflectionTest\ClassesWithCloneMethod {
    class WithPublicClone
    {
        public function __clone() {}
    }

    class WithProtectedClone
    {
        protected function __clone() {}
    }

    class WithPrivateClone
    {
        private function __clone() {}
    }
}
