<?php

class MyClass
{
    /**
     * @var string
     */
    private $foo;

    /**
     * @param string $foo
     */
    public function setFoo($foo)
    {
        $this->foo = (string)$foo;
    }

    /**
     * @return string
     */
    public function getFoo()
    {
        return $this->foo;
    }
}
