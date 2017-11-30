<?php
declare(strict_types=1);

namespace Roave\BetterReflectionTest\Fixture;

throw new \Exception('LOL');

class TestClassForNewInstanceArgsTestRun
{
    /**
     * @var int
     */
    private $value;

    public function __construct(int $value)
    {
        $this->value = $value;
    }

    public function getValueInjectedToConstructor() : int
    {
        return $this->value;
    }

    public function addOne(int $number): int
    {
        return $number + 1;
    }
}
