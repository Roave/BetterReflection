<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use Generator;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use Roave\BetterReflection\Reflection\Exception\InvalidConstantNode;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\SourceStubber\Exception\CouldNotFindPhpStormStubs;
use Roave\BetterReflection\Util\ConstantNodeChecker;
use Traversable;

use function array_change_key_case;
use function array_key_exists;
use function array_map;
use function assert;
use function constant;
use function count;
use function defined;
use function explode;
use function file_get_contents;
use function in_array;
use function is_dir;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function strtolower;
use function strtoupper;

/**
 * @internal
 */
final class PhpStormStubsSourceStubber implements SourceStubber
{
    private const BUILDER_OPTIONS    = ['shortArraySyntax' => true];
    private const SEARCH_DIRECTORIES = [
        __DIR__ . '/../../../../../jetbrains/phpstorm-stubs',
        __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs',
    ];

    private BuilderFactory $builderFactory;

    private Standard $prettyPrinter;

    private NodeTraverser $nodeTraverser;

    private ?string $stubsDirectory = null;

    private NodeVisitorAbstract $cachingVisitor;

    /**
     * `null` means "class is not supported in the required PHP version"
     *
     * @var array<string, Node\Stmt\ClassLike|null>
     */
    private array $classNodes = [];

    /**
     * `null` means "function is not supported in the required PHP version"
     *
     * @var array<string, Node\Stmt\Function_|null>
     */
    private array $functionNodes = [];

    /**
     * `null` means "failed lookup" for constant that is not case insensitive or "constant is not supported in the required PHP version"
     *
     * @var array<string, Node\Stmt\Const_|Node\Expr\FuncCall|null>
     */
    private array $constantNodes = [];

    private static bool $mapsInitialized = false;

    /** @var array<lowercase-string, string> */
    private static array $classMap;

    /** @var array<lowercase-string, string> */
    private static array $functionMap;

    /** @var array<lowercase-string, string> */
    private static array $constantMap;

    public function __construct(private Parser $phpParser, private ?int $phpVersion = null)
    {
        $this->builderFactory = new BuilderFactory();
        $this->prettyPrinter  = new Standard(self::BUILDER_OPTIONS);

        $this->cachingVisitor = $this->createCachingVisitor();

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
        $this->nodeTraverser->addVisitor($this->cachingVisitor);

        if (self::$mapsInitialized) {
            return;
        }

        self::$classMap        = array_change_key_case(PhpStormStubsMap::CLASSES);
        self::$functionMap     = array_change_key_case(PhpStormStubsMap::FUNCTIONS);
        self::$constantMap     = array_change_key_case(PhpStormStubsMap::CONSTANTS);
        self::$mapsInitialized = true;
    }

    public function generateClassStub(string $className): ?StubData
    {
        $lowercaseClassName = strtolower($className);

        if (! array_key_exists($lowercaseClassName, self::$classMap)) {
            return null;
        }

        $filePath = self::$classMap[$lowercaseClassName];

        if (! array_key_exists($lowercaseClassName, $this->classNodes)) {
            $this->parseFile($filePath);

            /** @psalm-suppress RedundantCondition */
            if (! array_key_exists($lowercaseClassName, $this->classNodes)) {
                // Save `null` so we don't parse the file again for the same $lowercaseClassName
                $this->classNodes[$lowercaseClassName] = null;
            }
        }

        if ($this->classNodes[$lowercaseClassName] === null) {
            return null;
        }

        $stub = $this->createStub($this->classNodes[$lowercaseClassName]);

        if ($className === Traversable::class) {
            // See https://github.com/JetBrains/phpstorm-stubs/commit/0778a26992c47d7dbee4d0b0bfb7fad4344371b1#diff-575bacb45377d474336c71cbf53c1729
            $stub = str_replace(' extends \iterable', '', $stub);
        } elseif ($className === Generator::class) {
            $stub = str_replace('PS_UNRESERVE_PREFIX_throw', 'throw', $stub);
        }

        return new StubData($stub, $this->getExtensionFromFilePath($filePath));
    }

    public function generateFunctionStub(string $functionName): ?StubData
    {
        $lowercaseFunctionName = strtolower($functionName);

        if (! array_key_exists($lowercaseFunctionName, self::$functionMap)) {
            return null;
        }

        $filePath = self::$functionMap[$lowercaseFunctionName];

        if (! array_key_exists($lowercaseFunctionName, $this->functionNodes)) {
            $this->parseFile($filePath);

            /** @psalm-suppress RedundantCondition */
            if (! array_key_exists($lowercaseFunctionName, $this->functionNodes)) {
                 // Save `null` so we don't parse the file again for the same $lowercaseFunctionName
                 $this->functionNodes[$lowercaseFunctionName] = null;
            }
        }

        if ($this->functionNodes[$lowercaseFunctionName] === null) {
            return null;
        }

        return new StubData($this->createStub($this->functionNodes[$lowercaseFunctionName]), $this->getExtensionFromFilePath($filePath));
    }

    public function generateConstantStub(string $constantName): ?StubData
    {
        $lowercaseConstantName = strtolower($constantName);

        if (! array_key_exists($lowercaseConstantName, self::$constantMap)) {
            return null;
        }

        if (
            array_key_exists($lowercaseConstantName, $this->constantNodes)
            && $this->constantNodes[$lowercaseConstantName] === null
        ) {
            return null;
        }

        $filePath     = self::$constantMap[$lowercaseConstantName];
        $constantNode = $this->constantNodes[$constantName] ?? $this->constantNodes[$lowercaseConstantName] ?? null;

        if ($constantNode === null) {
            $this->parseFile($filePath);

            $constantNode = $this->constantNodes[$constantName] ?? $this->constantNodes[$lowercaseConstantName] ?? null;

            if ($constantNode === null) {
                // Still `null` - the constant is not case-insensitive. Save `null` so we don't parse the file again for the same $constantName
                $this->constantNodes[$lowercaseConstantName] = null;

                return null;
            }
        }

        return new StubData($this->createStub($constantNode), $this->getExtensionFromFilePath($filePath));
    }

    private function parseFile(string $filePath): void
    {
        $absoluteFilePath = $this->getAbsoluteFilePath($filePath);
        FileChecker::assertReadableFile($absoluteFilePath);

        $ast = $this->phpParser->parse(file_get_contents($absoluteFilePath));

        /** @psalm-suppress UndefinedMethod */
        $this->cachingVisitor->clearNodes();

        $this->nodeTraverser->traverse($ast);

        /**
         * @psalm-suppress UndefinedMethod
         */
        foreach ($this->cachingVisitor->getClassNodes() as $className => $classNode) {
            assert(is_string($className));
            assert($classNode instanceof Node\Stmt\ClassLike);

            if (! $this->isSupportedInPhpVersion($classNode)) {
                continue;
            }

            $classNode->stmts = $this->modifyStmtsByPhpVersion($classNode->stmts);

            $this->classNodes[strtolower($className)] = $classNode;
        }

        /**
         * @psalm-suppress UndefinedMethod
         */
        foreach ($this->cachingVisitor->getFunctionNodes() as $functionName => $functionNode) {
            assert(is_string($functionName));
            assert($functionNode instanceof Node\Stmt\Function_);

            if (! $this->isSupportedInPhpVersion($functionNode)) {
                continue;
            }

            $this->functionNodes[strtolower($functionName)] = $functionNode;
        }

        /**
         * @psalm-suppress UndefinedMethod
         */
        foreach ($this->cachingVisitor->getConstantNodes() as $constantName => $constantNode) {
            assert(is_string($constantName));
            assert($constantNode instanceof Node\Stmt\Const_ || $constantNode instanceof Node\Expr\FuncCall);

            if (! $this->isSupportedInPhpVersion($constantNode)) {
                continue;
            }

            $this->constantNodes[$constantName] = $constantNode;
        }
    }

    /**
     * @param Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall $node
     */
    private function createStub(Node $node): string
    {
        if (! ($node instanceof Node\Expr\FuncCall)) {
            $this->addDeprecatedDocComment($node);

            $nodeWithNamespaceName = $node instanceof Node\Stmt\Const_ ? $node->consts[0] : $node;

            $namespaceBuilder = $this->builderFactory->namespace($nodeWithNamespaceName->namespacedName->slice(0, -1));
            $namespaceBuilder->addStmt($node);

            $node = $namespaceBuilder->getNode();
        }

        return "<?php\n\n" . $this->prettyPrinter->prettyPrint([$node]) . ($node instanceof Node\Expr\FuncCall ? ';' : '') . "\n";
    }

    private function createCachingVisitor(): NodeVisitorAbstract
    {
        return new class () extends NodeVisitorAbstract
        {
            /** @var array<string, Node\Stmt\ClassLike> */
            private array $classNodes = [];

            /** @var array<string, Node\Stmt\Function_> */
            private array $functionNodes = [];

            /** @var array<string, Node\Stmt\Const_|Node\Expr\FuncCall> */
            private array $constantNodes = [];

            public function enterNode(Node $node): ?int
            {
                if ($node instanceof Node\Stmt\ClassLike) {
                    $nodeName                    = $node->namespacedName->toString();
                    $this->classNodes[$nodeName] = $node;

                    foreach ($node->getConstants() as $constantsNode) {
                        foreach ($constantsNode->consts as $constNode) {
                            $constClassName = sprintf('%s::%s', $nodeName, $constNode->name->toString());
                            $this->updateConstantValue($constNode, $constClassName);
                        }
                    }

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof Node\Stmt\Function_) {
                    $nodeName                       = $node->namespacedName->toString();
                    $this->functionNodes[$nodeName] = $node;

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof Node\Stmt\Const_) {
                    foreach ($node->consts as $constNode) {
                        $constNodeName = $constNode->namespacedName->toString();

                        $this->updateConstantValue($constNode, $constNodeName);

                        $this->constantNodes[$constNodeName] = $node;
                    }

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                if ($node instanceof Node\Expr\FuncCall) {
                    try {
                        ConstantNodeChecker::assertValidDefineFunctionCall($node);
                    } catch (InvalidConstantNode) {
                        return null;
                    }

                    $nameNode = $node->args[0]->value;
                    assert($nameNode instanceof Node\Scalar\String_);
                    $constantName = $nameNode->value;

                    if (in_array($constantName, ['true', 'false', 'null'], true)) {
                        $constantName    = strtoupper($constantName);
                        $nameNode->value = $constantName;
                    }

                    $this->updateConstantValue($node, $constantName);

                    $this->constantNodes[$constantName] = $node;

                    if (
                        count($node->args) === 3
                        && $node->args[2]->value instanceof Node\Expr\ConstFetch
                        && $node->args[2]->value->name->toLowerString() === 'true'
                    ) {
                        $this->constantNodes[strtolower($constantName)] = $node;
                    }

                    return NodeTraverser::DONT_TRAVERSE_CHILDREN;
                }

                return null;
            }

            /**
             * @return array<string, Node\Stmt\ClassLike>
             */
            public function getClassNodes(): array
            {
                return $this->classNodes;
            }

            /**
             * @return array<string, Node\Stmt\Function_>
             */
            public function getFunctionNodes(): array
            {
                return $this->functionNodes;
            }

            /**
             * @return array<string, Node\Stmt\Const_|Node\Expr\FuncCall>
             */
            public function getConstantNodes(): array
            {
                return $this->constantNodes;
            }

            public function clearNodes(): void
            {
                $this->classNodes    = [];
                $this->functionNodes = [];
                $this->constantNodes = [];
            }

            /**
             * Some constants has different values on different systems, some are not actual in stubs.
             */
            private function updateConstantValue(Node\Expr\FuncCall|Node\Const_ $node, string $constantName): void
            {
                if (! defined($constantName)) {
                    return;
                }

                // @ because access to deprecated constant throws deprecated warning
                /** @var scalar|list<scalar>|null $constantValue */
                $constantValue           = @constant($constantName);
                $normalizedConstantValue = BuilderHelpers::normalizeValue($constantValue);

                if ($node instanceof Node\Expr\FuncCall) {
                    $node->args[1]->value = $normalizedConstantValue;
                } else {
                    $node->value = $normalizedConstantValue;
                }
            }
        };
    }

    private function getExtensionFromFilePath(string $filePath): string
    {
        return explode('/', $filePath)[0];
    }

    private function getAbsoluteFilePath(string $filePath): string
    {
        return sprintf('%s/%s', $this->getStubsDirectory(), $filePath);
    }

    /**
     * @param Node\Stmt[] $stmts
     *
     * @return Node\Stmt[]
     */
    private function modifyStmtsByPhpVersion(array $stmts): array
    {
        $newStmts = [];
        foreach ($stmts as $stmt) {
            if (! $this->isSupportedInPhpVersion($stmt)) {
                continue;
            }

            if (
                $stmt instanceof Node\Stmt\ClassConst
                || $stmt instanceof Node\Stmt\Property
                || $stmt instanceof Node\Stmt\ClassMethod
            ) {
                $this->addDeprecatedDocComment($stmt);
            }

            $newStmts[] = $stmt;
        }

        return $newStmts;
    }

    private function addDeprecatedDocComment(Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Stmt\Const_ $node): void
    {
        if ($node instanceof Node\Stmt\Const_) {
            return;
        }

        if (! $this->isDeprecatedInPhpVersion($node)) {
            return;
        }

        $docComment = $node->getDocComment();

        if ($docComment === null) {
            $docCommentText = '/** @deprecated */';
        } else {
            $docCommentText = preg_replace('~(\r?\n\s*)\*/~', '\1* @deprecated\1*/', $docComment->getText());
        }

        $node->setDocComment(new Doc($docCommentText));
    }

    private function isDeprecatedInPhpVersion(Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node): bool
    {
        foreach ($node->attrGroups as $attributesGroupNode) {
            foreach ($attributesGroupNode->attrs as $attributeNode) {
                // The name is sometimes FQN and sometimes not
                if (
                    $attributeNode->name->toString() !== 'JetBrains\PhpStorm\Deprecated'
                    && $attributeNode->name->toString() !== 'Deprecated'
                ) {
                    continue;
                }

                if ($this->phpVersion === null) {
                    return true;
                }

                foreach ($attributeNode->args as $attributeArg) {
                    if ($attributeArg->name?->toString() === 'since') {
                        assert($attributeArg->value instanceof Node\Scalar\String_);

                        return $this->parsePhpVersion($attributeArg->value->value) <= $this->phpVersion;
                    }
                }

                return true;
            }
        }

        return false;
    }

    private function isSupportedInPhpVersion(Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall|Node\Stmt $node): bool
    {
        if ($this->phpVersion === null) {
            return true;
        }

        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return true;
        }

        if (preg_match('~@since\s+(\d+\.\d+(?:\.\d+)?)~', $docComment->getText(), $sinceMatches) === 1) {
            $sincePhpVersion = $this->parsePhpVersion($sinceMatches[1]);

            if ($sincePhpVersion > $this->phpVersion) {
                return false;
            }
        }

        if (preg_match('~@removed\s+(\d+\.\d+(?:\.\d+)?)~', $docComment->getText(), $removedMatches) === 1) {
            $removedPhpVersion = $this->parsePhpVersion($removedMatches[1]);

            if ($removedPhpVersion <= $this->phpVersion) {
                return false;
            }
        }

        return true;
    }

    private function parsePhpVersion(string $version): int
    {
        $parts = array_map('intval', explode('.', $version));

        return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? 0);
    }

    private function getStubsDirectory(): string
    {
        if ($this->stubsDirectory !== null) {
            return $this->stubsDirectory;
        }

        foreach (self::SEARCH_DIRECTORIES as $directory) {
            if (is_dir($directory)) {
                return $this->stubsDirectory = $directory;
            }
        }

        // @codeCoverageIgnoreStart
        // Untestable code
        throw CouldNotFindPhpStormStubs::create();
        // @codeCoverageIgnoreEnd
    }
}
