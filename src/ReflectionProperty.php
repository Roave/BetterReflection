<?php

namespace Asgrim;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property as PropertyNode;

class ReflectionProperty
{
    /**
     * @var string
     */
    private $name;

    private function __construct()
    {
    }

    /**
     * @param PropertyNode $node
     * @return ReflectionProperty
     */
    public static function createFromNode(PropertyNode $node)
    {
        $prop = new self();
        $prop->name = $node->props[0]->name;

        return $prop;
    }

    /**
     * Get the name of the property
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
