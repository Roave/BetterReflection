<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use CompileError;
use DatePeriod;
use Error;
use Generator;
use Iterator;
use IteratorAggregate;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use JsonSerializable;
use ParseError;
use PDOStatement;
use PhpParser\BuilderFactory;
use PhpParser\BuilderHelpers;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use RecursiveIterator;
use Roave\BetterReflection\Reflection\Annotation\AnnotationHelper;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\SourceStubber\Exception\CouldNotFindPhpStormStubs;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs\CachingVisitor;
use SimpleXMLElement;
use SplFixedArray;
use Traversable;

use function array_change_key_case;
use function array_key_exists;
use function array_map;
use function assert;
use function explode;
use function file_get_contents;
use function in_array;
use function is_dir;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function usort;

use const PHP_VERSION_ID;

/** @internal */
final class PhpStormStubsSourceStubber implements SourceStubber
{
    private const BUILDER_OPTIONS    = ['shortArraySyntax' => true];
    private const SEARCH_DIRECTORIES = [
        __DIR__ . '/../../../../../jetbrains/phpstorm-stubs',
        __DIR__ . '/../../../vendor/jetbrains/phpstorm-stubs',
    ];
    private const CORE_EXTENSIONS    =    [
        'apache',
        'bcmath',
        'bz2',
        'calendar',
        'Core',
        'ctype',
        'curl',
        'date',
        'dba',
        'dom',
        'enchant',
        'exif',
        'FFI',
        'fileinfo',
        'filter',
        'fpm',
        'ftp',
        'gd',
        'gettext',
        'gmp',
        'hash',
        'iconv',
        'imap',
        'interbase',
        'intl',
        'json',
        'ldap',
        'libxml',
        'mbstring',
        'mcrypt',
        'mssql',
        'mysql',
        'mysqli',
        'oci8',
        'odbc',
        'openssl',
        'pcntl',
        'pcre',
        'PDO',
        'pdo_ibm',
        'pdo_mysql',
        'pdo_pgsql',
        'pdo_sqlite',
        'pgsql',
        'Phar',
        'posix',
        'pspell',
        'readline',
        'recode',
        'Reflection',
        'regex',
        'session',
        'shmop',
        'SimpleXML',
        'snmp',
        'soap',
        'sockets',
        'sodium',
        'SPL',
        'sqlite3',
        'standard',
        'sybase',
        'sysvmsg',
        'sysvsem',
        'sysvshm',
        'tidy',
        'tokenizer',
        'wddx',
        'xml',
        'xmlreader',
        'xmlrpc',
        'xmlwriter',
        'xsl',
        'Zend OPcache',
        'zip',
        'zlib',
    ];

    private BuilderFactory $builderFactory;

    private Standard $prettyPrinter;

    private NodeTraverser $nodeTraverser;

    private string|null $stubsDirectory = null;

    private CachingVisitor $cachingVisitor;

    /**
     * `null` means "class is not supported in the required PHP version"
     *
     * @var array<string, array{0: Node\Stmt\ClassLike, 1: Node\Stmt\Namespace_|null}|null>
     */
    private array $classNodes = [];

    /**
     * `null` means "function is not supported in the required PHP version"
     *
     * @var array<string, array{0: Node\Stmt\Function_, 1: Node\Stmt\Namespace_|null}|null>
     */
    private array $functionNodes = [];

    /**
     * `null` means "failed lookup" for constant that is not case insensitive or "constant is not supported in the required PHP version"
     *
     * @var array<string, array{0: Node\Stmt\Const_|Node\Expr\FuncCall, 1: Node\Stmt\Namespace_|null}|null>
     */
    private array $constantNodes = [];

    private static bool $mapsInitialized = false;

    /** @var array<lowercase-string, string> */
    private static array $classMap;

    /** @var array<lowercase-string, string> */
    private static array $functionMap;

    /** @var array<lowercase-string, string> */
    private static array $constantMap;

    public function __construct(private Parser $phpParser, private int $phpVersion = PHP_VERSION_ID)
    {
        $this->builderFactory = new BuilderFactory();
        $this->prettyPrinter  = new Standard(self::BUILDER_OPTIONS);

        $this->cachingVisitor = new CachingVisitor($this->builderFactory);

        $this->nodeTraverser = new NodeTraverser();
        $this->nodeTraverser->addVisitor(new NameResolver());
        $this->nodeTraverser->addVisitor($this->cachingVisitor);

        if (self::$mapsInitialized) {
            return;
        }

        /** @psalm-suppress PropertyTypeCoercion */
        self::$classMap = array_change_key_case(PhpStormStubsMap::CLASSES);
        /** @psalm-suppress PropertyTypeCoercion */
        self::$functionMap = array_change_key_case(PhpStormStubsMap::FUNCTIONS);
        /** @psalm-suppress PropertyTypeCoercion */
        self::$constantMap = array_change_key_case(PhpStormStubsMap::CONSTANTS);

        self::$mapsInitialized = true;
    }

    /** @param class-string|trait-string $className */
    public function generateClassStub(string $className): StubData|null
    {
        $classNodeData = $this->getClassNodeData($className);

        if ($classNodeData === null) {
            return null;
        }

        $classNode = $classNodeData[0];

        if ($classNode instanceof Node\Stmt\Class_) {
            if ($classNode->extends !== null) {
                $modifiedExtends    = $this->replaceExtendsOrImplementsByPhpVersion($className, [$classNode->extends]);
                $classNode->extends = $modifiedExtends !== [] ? $modifiedExtends[0] : null;
            }

            $classNode->implements = $this->replaceExtendsOrImplementsByPhpVersion($className, $classNode->implements);
        } elseif ($classNode instanceof Node\Stmt\Interface_) {
            $classNode->extends = $this->replaceExtendsOrImplementsByPhpVersion($className, $classNode->extends);
        }

        $extension = $this->getExtensionFromFilePath(self::$classMap[strtolower($className)]);
        $stub      = $this->createStub($classNode, $classNodeData[1]);

        if ($className === Traversable::class) {
            // See https://github.com/JetBrains/phpstorm-stubs/commit/0778a26992c47d7dbee4d0b0bfb7fad4344371b1#diff-575bacb45377d474336c71cbf53c1729
            $stub = str_replace(' extends \iterable', '', $stub);
        } elseif ($className === Generator::class) {
            $stub = str_replace('PS_UNRESERVE_PREFIX_throw', 'throw', $stub);
        }

        return new StubData($stub, $extension);
    }

    /** @return array{0: Node\Stmt\ClassLike, 1: Node\Stmt\Namespace_|null}|null */
    private function getClassNodeData(string $className): array|null
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

        return $this->classNodes[$lowercaseClassName];
    }

    public function generateFunctionStub(string $functionName): StubData|null
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

        $functionNodeData = $this->functionNodes[$lowercaseFunctionName];
        $extension        = $this->getExtensionFromFilePath($filePath);

        return new StubData($this->createStub($functionNodeData[0], $functionNodeData[1]), $extension);
    }

    public function generateConstantStub(string $constantName): StubData|null
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

        $filePath         = self::$constantMap[$lowercaseConstantName];
        $constantNodeData = $this->constantNodes[$constantName] ?? $this->constantNodes[$lowercaseConstantName] ?? null;

        if ($constantNodeData === null) {
            $this->parseFile($filePath);

            $constantNodeData = $this->constantNodes[$constantName] ?? $this->constantNodes[$lowercaseConstantName] ?? null;

            if ($constantNodeData === null) {
                // Still `null` - the constant is not case-insensitive. Save `null` so we don't parse the file again for the same $constantName
                $this->constantNodes[$lowercaseConstantName] = null;

                return null;
            }
        }

        $extension = $this->getExtensionFromFilePath($filePath);

        return new StubData($this->createStub($constantNodeData[0], $constantNodeData[1]), $extension);
    }

    private function parseFile(string $filePath): void
    {
        $absoluteFilePath = $this->getAbsoluteFilePath($filePath);
        FileChecker::assertReadableFile($absoluteFilePath);

        /** @var list<Node\Stmt> $ast */
        $ast = $this->phpParser->parse(file_get_contents($absoluteFilePath));

        // "@since" and "@removed" annotations in some cases do not contain a PHP version, but an extension version - e.g. "@since 1.3.0"
        // So we check PHP version only for stubs of core extensions
        $isCoreExtension = $this->isCoreExtension($this->getExtensionFromFilePath($filePath));

        $this->cachingVisitor->clearNodes();

        $this->nodeTraverser->traverse($ast);

        foreach ($this->cachingVisitor->getClassNodes() as $className => $classNodeData) {
            [$classNode] = $classNodeData;

            if ($isCoreExtension) {
                if (! $this->isSupportedInPhpVersion($classNode)) {
                    continue;
                }

                $classNode->stmts = $this->modifyStmtsByPhpVersion($classNode->stmts);
            }

            $this->classNodes[strtolower($className)] = $classNodeData;
        }

        foreach ($this->cachingVisitor->getFunctionNodes() as $functionName => $functionNodesData) {
            foreach ($functionNodesData as $functionNodeData) {
                [$functionNode] = $functionNodeData;

                if ($isCoreExtension) {
                    if (! $this->isSupportedInPhpVersion($functionNode)) {
                        continue;
                    }

                    $this->modifyFunctionReturnTypeByPhpVersion($functionNode);
                    $this->modifyFunctionParametersByPhpVersion($functionNode);
                }

                $lowercaseFunctionName = strtolower($functionName);

                if (array_key_exists($lowercaseFunctionName, $this->functionNodes)) {
                    continue;
                }

                $this->functionNodes[$lowercaseFunctionName] = $functionNodeData;
            }
        }

        foreach ($this->cachingVisitor->getConstantNodes() as $constantName => $constantNodeData) {
            [$constantNode] = $constantNodeData;

            if ($isCoreExtension && ! $this->isSupportedInPhpVersion($constantNode)) {
                continue;
            }

            $this->constantNodes[$constantName] = $constantNodeData;
        }
    }

    private function createStub(Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall $node, Node\Stmt\Namespace_|null $namespaceNode): string
    {
        if (! ($node instanceof Node\Expr\FuncCall)) {
            $this->addDeprecatedDocComment($node);

            $nodeWithNamespaceName = $node instanceof Node\Stmt\Const_ ? $node->consts[0] : $node;
            $namespacedName        = $nodeWithNamespaceName->namespacedName;
            assert($namespacedName instanceof Node\Name);

            $namespaceBuilder = $this->builderFactory->namespace($namespacedName->slice(0, -1));

            if ($namespaceNode !== null) {
                foreach ($namespaceNode->stmts as $stmt) {
                    if (! ($stmt instanceof Node\Stmt\Use_) && ! ($stmt instanceof Node\Stmt\GroupUse)) {
                        continue;
                    }

                    $namespaceBuilder->addStmt($stmt);
                }
            }

            $namespaceBuilder->addStmt($node);

            $node = $namespaceBuilder->getNode();
        }

        return sprintf(
            "<?php\n\n%s%s\n",
            $this->prettyPrinter->prettyPrint([$node]),
            $node instanceof Node\Expr\FuncCall ? ';' : '',
        );
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
     * Some stubs extend/implement classes from newer PHP versions. We need to filter those names in regard to set PHP version so that those stubs remain valid.
     *
     * @param array<Node\Name> $nameNodes
     *
     * @return list<Node\Name>
     */
    private function replaceExtendsOrImplementsByPhpVersion(string $className, array $nameNodes): array
    {
        $modifiedNames = [];
        foreach ($nameNodes as $nameNode) {
            $name = $nameNode->toString();

            if ($className === ParseError::class) {
                if ($name === CompileError::class && $this->phpVersion < 70300) {
                    $modifiedNames[] = new Node\Name\FullyQualified(Error::class);
                    continue;
                }
            } elseif ($className === SplFixedArray::class) {
                if ($name === JsonSerializable::class && $this->phpVersion < 80100) {
                    continue;
                }

                if ($name === IteratorAggregate::class && $this->phpVersion < 80000) {
                    continue;
                }

                if ($name === Iterator::class && $this->phpVersion >= 80000) {
                    continue;
                }
            } elseif ($className === SimpleXMLElement::class) {
                if ($name === RecursiveIterator::class && $this->phpVersion < 80000) {
                    continue;
                }
            } elseif ($className === DatePeriod::class || $className === PDOStatement::class) {
                if ($name === IteratorAggregate::class && $this->phpVersion < 80000) {
                    $modifiedNames[] = new Node\Name\FullyQualified(Traversable::class);
                    continue;
                }
            }

            if ($this->getClassNodeData($name) === null) {
                continue;
            }

            $modifiedNames[] = $nameNode;
        }

        return $modifiedNames;
    }

    /**
     * @param array<Node\Stmt> $stmts
     *
     * @return list<Node\Stmt>
     */
    private function modifyStmtsByPhpVersion(array $stmts): array
    {
        $newStmts = [];
        foreach ($stmts as $stmt) {
            assert($stmt instanceof Node\Stmt\ClassConst || $stmt instanceof Node\Stmt\Property || $stmt instanceof Node\Stmt\ClassMethod);

            if (! $this->isSupportedInPhpVersion($stmt)) {
                continue;
            }

            if ($stmt instanceof Node\Stmt\Property) {
                $this->modifyStmtTypeByPhpVersion($stmt);
            }

            if ($stmt instanceof Node\Stmt\ClassMethod) {
                $this->modifyFunctionReturnTypeByPhpVersion($stmt);
                $this->modifyFunctionParametersByPhpVersion($stmt);
            }

            $this->addDeprecatedDocComment($stmt);

            $newStmts[] = $stmt;
        }

        return $newStmts;
    }

    private function modifyStmtTypeByPhpVersion(Node\Stmt\Property|Node\Param $stmt): void
    {
        $type = $this->getStmtType($stmt);

        if ($type === null) {
            return;
        }

        $stmt->type = $type;
    }

    private function modifyFunctionReturnTypeByPhpVersion(Node\Stmt\ClassMethod|Node\Stmt\Function_ $function): void
    {
        $isTentativeReturnType = $this->getNodeAttribute($function, 'JetBrains\PhpStorm\Internal\TentativeType') !== null;

        if ($isTentativeReturnType) {
            // Tentative types are the most correct in stubs
            // If the type is tentative in stubs, we should remove the type for PHP < 8.1

            if ($this->phpVersion >= 80100) {
                $this->addAnnotationToDocComment($function, AnnotationHelper::TENTATIVE_RETURN_TYPE_ANNOTATION);
            } else {
                $function->returnType = null;
            }

            return;
        }

        $type = $this->getStmtType($function);

        if ($type === null) {
            return;
        }

        $function->returnType = $type;
    }

    private function modifyFunctionParametersByPhpVersion(Node\Stmt\ClassMethod|Node\Stmt\Function_ $function): void
    {
        $parameters = [];

        foreach ($function->getParams() as $parameterNode) {
            if (! $this->isSupportedInPhpVersion($parameterNode)) {
                continue;
            }

            $this->modifyStmtTypeByPhpVersion($parameterNode);

            $parameters[] = $parameterNode;
        }

        $function->params = $parameters;
    }

    private function getStmtType(Node\Stmt\Function_|Node\Stmt\ClassMethod|Node\Stmt\Property|Node\Param $node): Node\Name|Node\Identifier|Node\ComplexType|null
    {
        $languageLevelTypeAwareAttribute = $this->getNodeAttribute($node, 'JetBrains\PhpStorm\Internal\LanguageLevelTypeAware');

        if ($languageLevelTypeAwareAttribute === null) {
            return null;
        }

        assert($languageLevelTypeAwareAttribute->args[0]->value instanceof Node\Expr\Array_);

        /** @var list<Node\Expr\ArrayItem> $types */
        $types = $languageLevelTypeAwareAttribute->args[0]->value->items;

        usort($types, static fn (Node\Expr\ArrayItem $a, Node\Expr\ArrayItem $b): int => $b->key <=> $a->key);

        foreach ($types as $type) {
            assert($type->key instanceof Node\Scalar\String_);
            assert($type->value instanceof Node\Scalar\String_);

            if ($this->parsePhpVersion($type->key->value) > $this->phpVersion) {
                continue;
            }

            return $this->normalizeType($type->value->value);
        }

        assert($languageLevelTypeAwareAttribute->args[1]->value instanceof Node\Scalar\String_);

        return $languageLevelTypeAwareAttribute->args[1]->value->value !== ''
            ? $this->normalizeType($languageLevelTypeAwareAttribute->args[1]->value->value)
            : null;
    }

    private function addDeprecatedDocComment(Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Stmt\Const_ $node): void
    {
        if ($node instanceof Node\Stmt\Const_) {
            return;
        }

        if (! $this->isDeprecatedInPhpVersion($node)) {
            $this->removeAnnotationFromDocComment($node, 'deprecated');

            return;
        }

        $this->addAnnotationToDocComment($node, 'deprecated');
    }

    private function addAnnotationToDocComment(
        Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Stmt\Const_ $node,
        string $annotationName,
    ): void {
        $docComment = $node->getDocComment();

        if ($docComment === null) {
            $docCommentText = sprintf('/** @%s */', $annotationName);
        } else {
            $docCommentText = preg_replace('~(\r?\n\s*)\*/~', sprintf('\1* @%s\1*/', $annotationName), $docComment->getText());
        }

        $node->setDocComment(new Doc($docCommentText));
    }

    private function removeAnnotationFromDocComment(
        Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Stmt\Const_ $node,
        string $annotationName,
    ): void {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return;
        }

        $docCommentText = preg_replace('~@' . $annotationName . '.*$~m', '', $docComment->getText());
        $node->setDocComment(new Doc($docCommentText));
    }

    private function isCoreExtension(string $extension): bool
    {
        return in_array($extension, self::CORE_EXTENSIONS, true);
    }

    private function isDeprecatedInPhpVersion(Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node): bool
    {
        $deprecatedAttribute = $this->getNodeAttribute($node, 'JetBrains\PhpStorm\Deprecated');
        if ($deprecatedAttribute === null) {
            return false;
        }

        foreach ($deprecatedAttribute->args as $attributeArg) {
            if ($attributeArg->name?->toString() === 'since') {
                assert($attributeArg->value instanceof Node\Scalar\String_);

                return $this->parsePhpVersion($attributeArg->value->value) <= $this->phpVersion;
            }
        }

        return true;
    }

    private function isSupportedInPhpVersion(
        Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Param $node,
    ): bool {
        [$fromVersion, $toVersion] = $this->getSupportedPhpVersions($node);

        if ($fromVersion !== null && $fromVersion > $this->phpVersion) {
            return false;
        }

        return $toVersion === null || $toVersion >= $this->phpVersion;
    }

    /** @return array{0: int|null, 1: int|null} */
    private function getSupportedPhpVersions(
        Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Param $node,
    ): array {
        $fromVersion = null;
        $toVersion   = null;

        $docComment = $node->getDocComment();
        if ($docComment !== null) {
            if (preg_match('~@since\s+(?P<version>\d+\.\d+(?:\.\d+)?)\s+~', $docComment->getText(), $sinceMatches) === 1) {
                $fromVersion = $this->parsePhpVersion($sinceMatches['version']);
            }

            if (preg_match('~@removed\s+(?P<version>\d+\.\d+(?:\.\d+)?)\s+~', $docComment->getText(), $removedMatches) === 1) {
                $toVersion = $this->parsePhpVersion($removedMatches['version']) - 1;
            }
        }

        $elementsAvailable = $this->getNodeAttribute($node, 'JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable');
        if ($elementsAvailable !== null) {
            foreach ($elementsAvailable->args as $attributeArg) {
                if ($attributeArg->name === null || $attributeArg->name->toString() === 'from') {
                    assert($attributeArg->value instanceof Node\Scalar\String_);

                    $fromVersion = $this->parsePhpVersion($attributeArg->value->value);
                }

                if ($attributeArg->name?->toString() !== 'to') {
                    continue;
                }

                assert($attributeArg->value instanceof Node\Scalar\String_);

                $toVersion = $this->parsePhpVersion($attributeArg->value->value, 99);
            }
        }

        return [$fromVersion, $toVersion];
    }

    private function getNodeAttribute(
        Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Param $node,
        string $attributeName,
    ): Node\Attribute|null {
        if ($node instanceof Node\Expr\FuncCall || $node instanceof Node\Stmt\Const_) {
            return null;
        }

        foreach ($node->attrGroups as $attributesGroupNode) {
            foreach ($attributesGroupNode->attrs as $attributeNode) {
                if ($attributeNode->name->toString() === $attributeName) {
                    return $attributeNode;
                }
            }
        }

        return null;
    }

    private function parsePhpVersion(string $version, int $defaultPatch = 0): int
    {
        $parts = array_map('intval', explode('.', $version));

        return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
    }

    private function normalizeType(string $type): Node\Name|Node\Identifier|Node\ComplexType|null
    {
        // There are some invalid types in stubs, eg. `string[]|string|null`
        if (str_contains($type, '[')) {
            return null;
        }

        /** @psalm-suppress InternalClass, InternalMethod */
        return BuilderHelpers::normalizeType($type);
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
        // @infection-ignore-all
        // Untestable code
        throw CouldNotFindPhpStormStubs::create();
        // @codeCoverageIgnoreEnd
    }
}
