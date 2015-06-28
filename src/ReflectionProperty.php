<?php

namespace Asgrim;

use PhpParser\Node;
use PhpParser\Node\Stmt\Property as PropertyNode;
use phpDocumentor\Reflection\Type;

class ReflectionProperty
{
    const IS_PUBLIC = 1;
    const IS_PROTECTED = 2;
    const IS_PRIVATE = 3;

    /**
     * @var string
     */
    private $name;

    /**
     * @var int
     */
    private $visibility;

    /**
     * @var bool
     */
    private $isStatic;

    /**
     * @var Type[]
     */
    private $types;

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

        if ($node->isPrivate()) {
            $prop->visibility = self::IS_PRIVATE;
        } else if ($node->isProtected()) {
            $prop->visibility = self::IS_PROTECTED;
        } else {
            $prop->visibility = self::IS_PUBLIC;
        }

        $prop->isStatic = $node->isStatic();

        $prop->types = TypesFinder::findTypeForProperty($node);

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

    /**
     * Is the property private?
     *
     * @return bool
     */
    public function isPrivate()
    {
        return $this->visibility == self::IS_PRIVATE;
    }

    /**
     * Is the property protected?
     *
     * @return bool
     */
    public function isProtected()
    {
        return $this->visibility == self::IS_PROTECTED;
    }

    /**
     * Is the property public?
     *
     * @return bool
     */
    public function isPublic()
    {
        return $this->visibility == self::IS_PUBLIC;
    }

    /**
     * Is the property static?
     *
     * @return bool
     */
    public function isStatic()
    {
        return $this->isStatic;
    }

    /**
     * @return string[]
     */
    public function getTypes()
    {
        $stringTypes = [];

        foreach ($this->types as $type) {
            $stringTypes[] = (string)$type;
        }
        return $stringTypes;
    }

    /**
     * @return Type[]
     */
    public function getTypeObjects()
    {
        return $this->types;
    }
}
