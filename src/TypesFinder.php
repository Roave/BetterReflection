<?php

namespace BetterReflection;

use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\Node\Param as ParamNode;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\TypeResolver;
use phpDocumentor\Reflection\Type;
use phpDocumentor\Reflection\Types\ContextFactory;
use phpDocumentor\Reflection\Types\Context;

class TypesFinder
{
    /**
     * Given a property, attempt to find the type of the property
     *
     * @param PropertyNode $node
     * @param ReflectionProperty $reflectionProperty
     * @return Type[]
     */
    public static function findTypeForProperty(PropertyNode $node, ReflectionProperty $reflectionProperty)
    {
        $contextFactory = new ContextFactory();
        $context = $contextFactory->createFromReflector($reflectionProperty);

        /* @var \PhpParser\Comment\Doc $comment */
        if (!$node->hasAttribute('comments')) {
            return [];
        }
        $comment = $node->getAttribute('comments')[0];
        $docBlock = new DocBlock(
            $comment->getReformattedText(),
            new DocBlock\Context($reflectionProperty->getDeclaringClass()->getNamespaceName())
        );

        /* @var \phpDocumentor\Reflection\DocBlock\Tag\VarTag $varTag */
        $varTag = $docBlock->getTagsByName('var')[0];
        return self::resolveTypes($varTag->getTypes(), $context);
    }

    /**
     * Given a function and parameter, attempt to find the type of the parameter
     *
     * @param ReflectionFunctionAbstract $function
     * @param ParamNode $node
     * @return Type[]
     */
    public static function findTypeForParameter(ReflectionFunctionAbstract $function, ParamNode $node)
    {
        $docBlock = new DocBlock($function->getDocComment());

        $paramTags = $docBlock->getTagsByName('param');

        foreach ($paramTags as $paramTag) {
            /* @var $paramTag \phpDocumentor\Reflection\DocBlock\Tag\ParamTag */
            if ($paramTag->getVariableName() == '$' . $node->name) {
                return self::resolveTypes($paramTag->getTypes());
            }
        }
        return [];
    }

    /**
     * Given an AST type, attempt to find a resolved type
     *
     * @todo resolve with context
     * @param $astType
     * @return \phpDocumentor\Reflection\Type|null
     */
    public static function findTypeForAstType($astType)
    {
        if (is_string($astType)) {
            $typeString = $astType;
        }

        if ($astType instanceof FullyQualified) {
            $typeString = implode('\\', $astType->parts);
        }

        if (!isset($typeString)) {
            return null;
        }

        $types = self::resolveTypes([$typeString]);

        return reset($types);
    }

    /**
     * @param string[] $stringTypes
     * @param Context $context
     * @return \phpDocumentor\Reflection\Type[]
     */
    private static function resolveTypes($stringTypes, Context $context = null)
    {
        $resolvedTypes = [];
        $resolver = new TypeResolver();

        foreach ($stringTypes as $stringType) {
            $resolvedTypes[] = $resolver->resolve($stringType, $context);
        }

        return $resolvedTypes;
    }
}
