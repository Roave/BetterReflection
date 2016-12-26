<?php

namespace Roave\BetterReflection\TypesFinder;

use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use phpDocumentor\Reflection\Types\ContextFactory;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;

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
        static $contexts = [];

        if (false === isset($contexts[$namespace])) {
            $contexts[$namespace] = (new ContextFactory())->createForNamespace(
                $namespace,
                $locatedSource->getSource()
            );
        }

        $context = $contexts[$namespace];

        // @todo Nullable types are effectively ignored - to be fixed
        /* @see https://github.com/Roave/BetterReflection/issues/202 */
        if ($astType instanceof NullableType) {
            $astType = $astType->type;
        }

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
