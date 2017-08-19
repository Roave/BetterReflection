<?php
declare(strict_types=1);

namespace Roave\BetterReflection\TypesFinder;

use phpDocumentor\Reflection\Type;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use phpDocumentor\Reflection\Types\ContextFactory;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;

class FindTypeFromAst
{
    /**
     * Given an AST type, attempt to find a resolved type.
     *
     * @param string|Name|NullableType $astType
     * @param LocatedSource $locatedSource
     * @param string $namespace
     * @return \phpDocumentor\Reflection\Type|null
     */
    public function __invoke($astType, LocatedSource $locatedSource, string $namespace = '') : ?Type
    {
        // @todo Nullable types are effectively ignored - to be fixed
        /* @see https://github.com/Roave/BetterReflection/issues/202 */
        if ($astType instanceof NullableType) {
            $astType = $astType->type;
        }

        if (is_string($astType)) {
            return $this->typeStringToType($astType, $locatedSource, $namespace);
        }

        if ($astType instanceof Name\FullyQualified) {
            // If the AST determined this is a "fully qualified" name, prepend \
            return $this->typeStringToType('\\' . $astType->toString(), $locatedSource, $namespace);
        }

        if ($astType instanceof Name) {
            return $this->typeStringToType($astType->toString(), $locatedSource, $namespace);
        }

        return null;
    }

    private function typeStringToType(string $typeString, LocatedSource $locatedSource, string $namespace) : ?Type
    {
        $types = (new ResolveTypes())
            ->__invoke(
                [$typeString],
                (new ContextFactory())->createForNamespace(
                    $namespace,
                    $locatedSource->getSource()
                )
            );

        $firstType = reset($types);

        return $firstType ?: null;
    }
}
