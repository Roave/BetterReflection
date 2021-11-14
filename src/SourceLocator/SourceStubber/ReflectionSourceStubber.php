<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use LogicException;
use PhpParser\Builder\Class_;
use PhpParser\Builder\ClassConst;
use PhpParser\Builder\Function_;
use PhpParser\Builder\FunctionLike;
use PhpParser\Builder\Interface_;
use PhpParser\Builder\Method;
use PhpParser\Builder\Param;
use PhpParser\Builder\Property;
use PhpParser\Builder\Trait_;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation;
use PhpParser\Node\UnionType;
use PhpParser\PrettyPrinter\Standard;
use ReflectionClass as CoreReflectionClass;
use ReflectionClassConstant as CoreReflectionClassConstant;
use ReflectionFunction as CoreReflectionFunction;
use ReflectionFunctionAbstract as CoreReflectionFunctionAbstract;
use ReflectionIntersectionType as CoreReflectionIntersectionType;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionNamedType as CoreReflectionNamedType;
use ReflectionParameter;
use ReflectionProperty as CoreReflectionProperty;
use ReflectionUnionType as CoreReflectionUnionType;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;

use function array_diff;
use function array_key_exists;
use function assert;
use function class_exists;
use function explode;
use function function_exists;
use function get_defined_constants;
use function implode;
use function in_array;
use function interface_exists;
use function is_array;
use function method_exists;
use function preg_replace;
use function sprintf;
use function trait_exists;

/**
 * It generates a stub source from internal reflection for given class or function name.
 *
 * @internal
 */
final class ReflectionSourceStubber implements SourceStubber
{
    private const BUILDER_OPTIONS = ['shortArraySyntax' => true];

    private BuilderFactory $builderFactory;

    private Standard $prettyPrinter;

    public function __construct()
    {
        $this->builderFactory = new BuilderFactory();
        $this->prettyPrinter  = new Standard(self::BUILDER_OPTIONS);
    }

    public function generateClassStub(string $className): ?StubData
    {
        if (! (class_exists($className, false) || interface_exists($className, false) || trait_exists($className, false))) {
            return null;
        }

        /** @psalm-var class-string|trait-string $className */
        $classReflection = new CoreReflectionClass($className);
        $classNode       = $this->createClass($classReflection);

        if ($classNode instanceof Class_) {
            $this->addClassModifiers($classNode, $classReflection);
        }

        if ($classNode instanceof Class_ || $classNode instanceof Interface_) {
            $this->addExtendsAndImplements($classNode, $classReflection);
        }

        if ($classNode instanceof Class_ || $classNode instanceof Trait_) {
            $this->addProperties($classNode, $classReflection);
            $this->addTraitUse($classNode, $classReflection);
        }

        $this->addDocComment($classNode, $classReflection);
        $this->addConstants($classNode, $classReflection);
        $this->addMethods($classNode, $classReflection);

        $extensionName = $classReflection->getExtensionName() ?: null;

        if (! $classReflection->inNamespace()) {
            return $this->createStubData($this->generateStub($classNode->getNode()), $extensionName);
        }

        return $this->createStubData($this->generateStubInNamespace($classNode->getNode(), $classReflection->getNamespaceName()), $extensionName);
    }

    public function generateFunctionStub(string $functionName): ?StubData
    {
        if (! function_exists($functionName)) {
            return null;
        }

        $functionReflection = new CoreReflectionFunction($functionName);
        $functionNode       = $this->builderFactory->function($functionReflection->getShortName());

        $this->addDocComment($functionNode, $functionReflection);
        $this->addParameters($functionNode, $functionReflection);

        $returnType = $functionReflection->getReturnType();
        if ($returnType === null && method_exists($functionReflection, 'getTentativeReturnType')) {
            $returnType = $functionReflection->getTentativeReturnType();
        }

        assert($returnType instanceof CoreReflectionNamedType || $returnType instanceof CoreReflectionUnionType || $returnType instanceof CoreReflectionIntersectionType || $returnType === null);

        if ($returnType !== null) {
            $functionNode->setReturnType($this->formatType($returnType));
        }

        $extensionName = $functionReflection->getExtensionName() ?: null;

        if (! $functionReflection->inNamespace()) {
            return $this->createStubData($this->generateStub($functionNode->getNode()), $extensionName);
        }

        return $this->createStubData($this->generateStubInNamespace($functionNode->getNode(), $functionReflection->getNamespaceName()), $extensionName);
    }

    public function generateConstantStub(string $constantName): ?StubData
    {
        // Not supported because of resource as value
        if (in_array($constantName, ['STDIN', 'STDOUT', 'STDERR'], true)) {
            return null;
        }

        $constantData = $this->findConstantData($constantName);

        if ($constantData === null) {
            return null;
        }

        [$constantValue, $extensionName] = $constantData;

        $constantNode = $this->builderFactory->funcCall('define', [$constantName, $constantValue]);

        return $this->createStubData($this->generateStub($constantNode), $extensionName);
    }

    /**
     * @return array{0: scalar|list<scalar>|resource|null, 1: string|null}|null
     */
    private function findConstantData(string $constantName): ?array
    {
        /** @var array<string, array<string, scalar|list<scalar>|resource|null>> $constants */
        $constants = get_defined_constants(true);

        foreach ($constants as $constantExtensionName => $extensionConstants) {
            if (array_key_exists($constantName, $extensionConstants)) {
                return [
                    $extensionConstants[$constantName],
                    $constantExtensionName !== 'user' ? $constantExtensionName : null,
                ];
            }
        }

        return null;
    }

    private function createClass(CoreReflectionClass $classReflection): Class_|Interface_|Trait_
    {
        if ($classReflection->isTrait()) {
            return $this->builderFactory->trait($classReflection->getShortName());
        }

        if ($classReflection->isInterface()) {
            return $this->builderFactory->interface($classReflection->getShortName());
        }

        return $this->builderFactory->class($classReflection->getShortName());
    }

    private function addDocComment(
        Class_|Interface_|Trait_|Method|Property|Function_ $node,
        CoreReflectionClass|CoreReflectionMethod|CoreReflectionProperty|CoreReflectionFunction $reflection,
    ): void {
        $docComment  = $reflection->getDocComment() ?: '';
        $annotations = [];

        if (
            ($reflection instanceof CoreReflectionMethod || $reflection instanceof CoreReflectionFunction)
            && $reflection->isInternal()
        ) {
            if ($reflection->isDeprecated()) {
                $annotations[] = '@deprecated';
            }

            if (method_exists($reflection, 'hasTentativeReturnType') && $reflection->hasTentativeReturnType()) {
                $annotations[] = sprintf('@%s', AnnotationHelper::TENTATIVE_RETURN_TYPE_ANNOTATION);
            }
        }

        if ($docComment === '' && $annotations === []) {
            return;
        }

        if ($docComment === '') {
            $docComment = sprintf("/**\n* %s\n*/", implode("\n *", $annotations));
        } elseif ($annotations !== []) {
            $docComment = preg_replace('~\s+\*/$~', sprintf("\n* %s\n*/", implode("\n *", $annotations)), $docComment);
        }

        $node->setDocComment(new Doc($docComment));
    }

    private function addClassModifiers(Class_ $classNode, CoreReflectionClass $classReflection): void
    {
        if (! $classReflection->isInterface() && $classReflection->isAbstract()) {
            // Interface \Iterator is interface and abstract
            $classNode->makeAbstract();
        }

        if (! $classReflection->isFinal()) {
            return;
        }

        $classNode->makeFinal();
    }

    private function addExtendsAndImplements(Class_|Interface_ $classNode, CoreReflectionClass $classReflection): void
    {
        $parentClass = $classReflection->getParentClass();
        $interfaces  = $classReflection->getInterfaceNames();

        if ($parentClass) {
            $classNode->extend(new FullyQualified($parentClass->getName()));
            $interfaces = array_diff($interfaces, $parentClass->getInterfaceNames());
        }

        foreach ($classReflection->getInterfaces() as $interface) {
            $interfaces = array_diff($interfaces, $interface->getInterfaceNames());
        }

        foreach ($interfaces as $interfaceName) {
            if ($classNode instanceof Interface_) {
                $classNode->extend(new FullyQualified($interfaceName));
            } else {
                $classNode->implement(new FullyQualified($interfaceName));
            }
        }
    }

    private function addTraitUse(Class_|Trait_ $classNode, CoreReflectionClass $classReflection): void
    {
        $alreadyUsedTraitNames = [];

        $traitAliases = $classReflection->getTraitAliases();
        assert(is_array($traitAliases));

        foreach ($traitAliases as $methodNameAlias => $methodInfo) {
            [$traitName, $methodName] = explode('::', $methodInfo);
            $traitUseNode             = new TraitUse(
                [new FullyQualified($traitName)],
                [new TraitUseAdaptation\Alias(new FullyQualified($traitName), $methodName, null, $methodNameAlias)],
            );

            $classNode->addStmt($traitUseNode);

            $alreadyUsedTraitNames[] = $traitName;
        }

        foreach (array_diff($classReflection->getTraitNames(), $alreadyUsedTraitNames) as $traitName) {
            $classNode->addStmt(new TraitUse([new FullyQualified($traitName)]));
        }
    }

    private function addProperties(Class_|Trait_ $classNode, CoreReflectionClass $classReflection): void
    {
        $defaultProperties = $classReflection->getDefaultProperties();

        foreach ($classReflection->getProperties() as $propertyReflection) {
            if (! $this->isPropertyDeclaredInClass($propertyReflection, $classReflection)) {
                continue;
            }

            $propertyNode = $this->builderFactory->property($propertyReflection->getName());

            $this->addPropertyModifiers($propertyNode, $propertyReflection);
            $this->addDocComment($propertyNode, $propertyReflection);

            if (array_key_exists($propertyReflection->getName(), $defaultProperties)) {
                try {
                    $propertyNode->setDefault($defaultProperties[$propertyReflection->getName()]);
                } catch (LogicException) {
                    // Unsupported value
                }
            }

            $propertyType = $propertyReflection->getType();

            assert($propertyType instanceof CoreReflectionNamedType || $propertyType instanceof CoreReflectionUnionType || $propertyType instanceof CoreReflectionIntersectionType || $propertyType === null);

            if ($propertyType !== null) {
                $propertyNode->setType($this->formatType($propertyType));
            }

            $classNode->addStmt($propertyNode);
        }
    }

    private function isPropertyDeclaredInClass(CoreReflectionProperty $propertyReflection, CoreReflectionClass $classReflection): bool
    {
        if ($propertyReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
            return false;
        }

        foreach ($classReflection->getTraits() as $trait) {
            if ($trait->hasProperty($propertyReflection->getName())) {
                return false;
            }
        }

        return true;
    }

    private function addPropertyModifiers(Property $propertyNode, CoreReflectionProperty $propertyReflection): void
    {
        if ($propertyReflection->isStatic()) {
            $propertyNode->makeStatic();
        }

        if ($propertyReflection->isPublic()) {
            $propertyNode->makePublic();
        }

        if ($propertyReflection->isProtected()) {
            $propertyNode->makeProtected();
        }

        if (! $propertyReflection->isPrivate()) {
            return;
        }

        $propertyNode->makePrivate();
    }

    private function addConstants(Class_|Interface_|Trait_ $classNode, CoreReflectionClass $classReflection): void
    {
        foreach ($classReflection->getReflectionConstants() as $constantReflection) {
            if ($constantReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
                continue;
            }

            $classConstantNode = $this->builderFactory->classConst($constantReflection->getName(), $constantReflection->getValue());

            if ($constantReflection->getDocComment() !== false) {
                $classConstantNode->setDocComment(new Doc($constantReflection->getDocComment()));
            }

            $this->addClassConstantModifiers($classConstantNode, $constantReflection);

            $classNode->addStmt($classConstantNode);
        }
    }

    private function addClassConstantModifiers(ClassConst $classConstantNode, CoreReflectionClassConstant $classConstantReflection): void
    {
        if ($classConstantReflection->isPrivate()) {
            $classConstantNode->makePrivate();
        } elseif ($classConstantReflection->isProtected()) {
            $classConstantNode->makeProtected();
        } else {
            $classConstantNode->makePublic();
        }
    }

    private function addMethods(Class_|Interface_|Trait_ $classNode, CoreReflectionClass $classReflection): void
    {
        foreach ($classReflection->getMethods() as $methodReflection) {
            if (! $this->isMethodDeclaredInClass($methodReflection, $classReflection)) {
                continue;
            }

            $methodNode = $this->builderFactory->method($methodReflection->getName());

            $this->addMethodFlags($methodNode, $methodReflection);
            $this->addDocComment($methodNode, $methodReflection);
            $this->addParameters($methodNode, $methodReflection);

            $returnType = $methodReflection->getReturnType();
            if ($returnType === null && method_exists($methodReflection, 'getTentativeReturnType')) {
                $returnType = $methodReflection->getTentativeReturnType();
            }

            assert($returnType instanceof CoreReflectionNamedType || $returnType instanceof CoreReflectionUnionType || $returnType instanceof CoreReflectionIntersectionType || $returnType === null);

            if ($returnType !== null) {
                $methodNode->setReturnType($this->formatType($returnType));
            }

            $classNode->addStmt($methodNode);
        }
    }

    private function isMethodDeclaredInClass(CoreReflectionMethod $methodReflection, CoreReflectionClass $classReflection): bool
    {
        if ($methodReflection->getDeclaringClass()->getName() !== $classReflection->getName()) {
            return false;
        }

        /** @var array<string, string> $traitAliases */
        $traitAliases = $classReflection->getTraitAliases();

        if (array_key_exists($methodReflection->getName(), $traitAliases)) {
            return false;
        }

        foreach ($classReflection->getTraits() as $trait) {
            if ($trait->hasMethod($methodReflection->getName())) {
                return false;
            }
        }

        return true;
    }

    private function addMethodFlags(Method $methodNode, CoreReflectionMethod $methodReflection): void
    {
        if ($methodReflection->isFinal()) {
            $methodNode->makeFinal();
        }

        if ($methodReflection->isAbstract() && ! $methodReflection->getDeclaringClass()->isInterface()) {
            $methodNode->makeAbstract();
        }

        if ($methodReflection->isStatic()) {
            $methodNode->makeStatic();
        }

        if ($methodReflection->isPublic()) {
            $methodNode->makePublic();
        }

        if ($methodReflection->isProtected()) {
            $methodNode->makeProtected();
        }

        if ($methodReflection->isPrivate()) {
            $methodNode->makePrivate();
        }

        if (! $methodReflection->returnsReference()) {
            return;
        }

        $methodNode->makeReturnByRef();
    }

    private function addParameters(FunctionLike $functionNode, CoreReflectionFunctionAbstract $functionReflectionAbstract): void
    {
        foreach ($functionReflectionAbstract->getParameters() as $parameterReflection) {
            $parameterNode = $this->builderFactory->param($parameterReflection->getName());

            $this->addParameterModifiers($parameterReflection, $parameterNode);
            $this->setParameterDefaultValue($parameterReflection, $parameterNode);

            $functionNode->addParam($parameterNode);
        }
    }

    private function addParameterModifiers(ReflectionParameter $parameterReflection, Param $parameterNode): void
    {
        if ($parameterReflection->isVariadic()) {
            $parameterNode->makeVariadic();
        }

        if ($parameterReflection->isPassedByReference()) {
            $parameterNode->makeByRef();
        }

        $parameterType = $parameterReflection->getType();

        assert($parameterType instanceof CoreReflectionNamedType || $parameterType instanceof CoreReflectionUnionType || $parameterType instanceof CoreReflectionIntersectionType || $parameterType === null);

        if ($parameterType === null) {
            return;
        }

        $parameterNode->setType($this->formatType($parameterType));
    }

    private function setParameterDefaultValue(ReflectionParameter $parameterReflection, Param $parameterNode): void
    {
        if (! $parameterReflection->isOptional()) {
            return;
        }

        if ($parameterReflection->isVariadic()) {
            return;
        }

        if (! $parameterReflection->isDefaultValueAvailable()) {
            return;
        }

        $parameterNode->setDefault($parameterReflection->getDefaultValue());
    }

    private function formatType(CoreReflectionNamedType|CoreReflectionUnionType|CoreReflectionIntersectionType $type): Name|FullyQualified|Node\NullableType|Node\UnionType|Node\IntersectionType
    {
        if ($type instanceof CoreReflectionIntersectionType) {
            $types = [];

            foreach ($type->getTypes() as $innerType) {
                assert($innerType instanceof CoreReflectionNamedType);
                $types[] = $this->formatNamedType($innerType);
            }

            return new IntersectionType($types);
        }

        if ($type instanceof CoreReflectionUnionType) {
            $types = [];

            foreach ($type->getTypes() as $innerType) {
                $types[] = $this->formatNamedType($innerType);
            }

            return new UnionType($types);
        }

        $name     = $type->getName();
        $nameNode = $this->formatNamedType($type);

        if (! $type->allowsNull() || $name === 'mixed') {
            return $nameNode;
        }

        return new NullableType($nameNode);
    }

    private function formatNamedType(CoreReflectionNamedType $type): Name|FullyQualified
    {
        $name = $type->getName();

        return $type->isBuiltin() || in_array($name, ['self', 'parent', 'static'], true) ? new Name($name) : new FullyQualified($name);
    }

    private function generateStubInNamespace(Node $node, string $namespaceName): string
    {
        $namespaceBuilder = $this->builderFactory->namespace($namespaceName);
        $namespaceBuilder->addStmt($node);

        return $this->generateStub($namespaceBuilder->getNode());
    }

    private function generateStub(Node $node): string
    {
        return "<?php\n\n" . $this->prettyPrinter->prettyPrint([$node]) . ($node instanceof Node\Expr\FuncCall ? ';' : '') . "\n";
    }

    private function createStubData(string $stub, ?string $extensionName): StubData
    {
        return new StubData($stub, $extensionName);
    }
}
