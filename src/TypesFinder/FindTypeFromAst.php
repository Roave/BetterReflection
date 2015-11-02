<?php

namespace BetterReflection\TypesFinder;

use BetterReflection\SourceLocator\Located\LocatedSource;
use phpDocumentor\Reflection\Types\ContextFactory;
use PhpParser\Node\Name;

class FindTypeFromAst
{
    /**
     * Given an AST type, attempt to find a resolved type.
     *
     * @param $astType
     * @param LocatedSource $locatedSource
     * @param string $namespace
     * @return \phpDocumentor\Reflection\Type|null
     */
    public function __invoke($astType, LocatedSource $locatedSource, $namespace = '')
    {
        $context = (new ContextFactory())->createForNamespace(
            $namespace,
            $locatedSource->getSource()
        );

        if (is_string($astType)) {
            $typeString = $astType;
        }

        if ($astType instanceof Name) {
            $typeString = $astType->toString();
        }

        // If the AST determined this is a "fully qualified" name, prepend \
        if ($astType instanceof Name\FullyQualified) {
            $typeString = '\\' . $typeString;
        }

        if (!isset($typeString)) {
            return null;
        }

        $types = (new ResolveTypes())->__invoke([$typeString], $context);

        return reset($types);
    }
}
