<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

use Generator;
use JetBrains\PHPStormStub\PhpStormStubsMap;
use PhpParser\BuilderFactory;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard;
use Roave\BetterReflection\SourceLocator\FileChecker;
use Roave\BetterReflection\SourceLocator\SourceStubber\Exception\CouldNotFindPhpStormStubs;
use Roave\BetterReflection\SourceLocator\SourceStubber\PhpStormStubs\CachingVisitor;
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
use function property_exists;
use function sprintf;
use function str_replace;
use function strtolower;

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

    private ?string $stubsDirectory = null;

    private CachingVisitor $cachingVisitor;

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
        self::$constantMap     = array_change_key_case(PhpStormStubsMap::CONSTANTS);
        self::$mapsInitialized = true;
    }

    /**
     * @param class-string|trait-string $className
     */
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

        $extension = $this->getExtensionFromFilePath($filePath);
        $stub      = $this->createStub($this->classNodes[$lowercaseClassName]);

        if ($className === Traversable::class) {
            // See https://github.com/JetBrains/phpstorm-stubs/commit/0778a26992c47d7dbee4d0b0bfb7fad4344371b1#diff-575bacb45377d474336c71cbf53c1729
            $stub = str_replace(' extends \iterable', '', $stub);
        } elseif ($className === Generator::class) {
            $stub = str_replace('PS_UNRESERVE_PREFIX_throw', 'throw', $stub);
        }

        return new StubData($stub, $extension);
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

        $extension = $this->getExtensionFromFilePath($filePath);

        return new StubData($this->createStub($this->functionNodes[$lowercaseFunctionName]), $extension);
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

        $extension = $this->getExtensionFromFilePath($filePath);

        return new StubData($this->createStub($constantNode), $extension);
    }

    private function parseFile(string $filePath): void
    {
        $absoluteFilePath = $this->getAbsoluteFilePath($filePath);
        FileChecker::assertReadableFile($absoluteFilePath);

        /** @var list<Node\Stmt> $ast */
        $ast             = $this->phpParser->parse(file_get_contents($absoluteFilePath));
        $isCoreExtension = $this->isCoreExtension($this->getExtensionFromFilePath($filePath));

        $this->cachingVisitor->clearNodes();

        $this->nodeTraverser->traverse($ast);

        foreach ($this->cachingVisitor->getClassNodes() as $className => $classNode) {
            if (! $this->isSupportedInPhpVersion($classNode, $isCoreExtension)) {
                continue;
            }

            $classNode->stmts = $this->modifyStmtsByPhpVersion($classNode->stmts, $isCoreExtension);

            $this->classNodes[strtolower($className)] = $classNode;
        }

        foreach ($this->cachingVisitor->getFunctionNodes() as $functionName => $functionNodes) {
            foreach ($functionNodes as $functionNode) {
                if (! $this->isSupportedInPhpVersion($functionNode, $isCoreExtension)) {
                    continue;
                }

                $this->functionNodes[strtolower($functionName)] = $functionNode;
            }
        }

        foreach ($this->cachingVisitor->getConstantNodes() as $constantName => $constantNode) {
            if (! $this->isSupportedInPhpVersion($constantNode, $isCoreExtension)) {
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
     * @param list<Node\Stmt> $stmts
     *
     * @return list<Node\Stmt>
     */
    private function modifyStmtsByPhpVersion(array $stmts, bool $isCoreExtension): array
    {
        $newStmts = [];
        foreach ($stmts as $stmt) {
            assert($stmt instanceof Node\Stmt\ClassConst || $stmt instanceof Node\Stmt\Property || $stmt instanceof Node\Stmt\ClassMethod);

            if (! $this->isSupportedInPhpVersion($stmt, $isCoreExtension)) {
                continue;
            }

            $this->addDeprecatedDocComment($stmt);

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

    private function isCoreExtension(string $extension): bool
    {
        return in_array($extension, self::CORE_EXTENSIONS, true);
    }

    private function isDeprecatedInPhpVersion(Node\Stmt\ClassLike|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod|Node\Stmt\Function_ $node): bool
    {
        foreach ($node->attrGroups as $attributesGroupNode) {
            foreach ($attributesGroupNode->attrs as $attributeNode) {
                if ($attributeNode->name->toString() !== 'JetBrains\PhpStorm\Deprecated') {
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

    private function isSupportedInPhpVersion(
        Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod $node,
        bool $isCoreExtension,
    ): bool {
        if ($this->phpVersion === null) {
            return true;
        }

        // "@since" and "@removed" annotations in some cases do not contain a PHP version, but an extension version - e.g. "@since 1.3.0"
        if (! $isCoreExtension) {
            return true;
        }

        [$fromVersion, $toVersion] = $this->getSupportedPhpVersions($node);

        if ($fromVersion !== null && $fromVersion > $this->phpVersion) {
            return false;
        }

        return $toVersion === null || $toVersion >= $this->phpVersion;
    }

    /**
     * @return array{0: int|null, 1: int|null}
     */
    private function getSupportedPhpVersions(
        Node\Stmt\ClassLike|Node\Stmt\Function_|Node\Stmt\Const_|Node\Expr\FuncCall|Node\Stmt\ClassConst|Node\Stmt\Property|Node\Stmt\ClassMethod $node,
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

        if (property_exists($node, 'attrGroups')) {
            /** @psalm-suppress UndefinedPropertyFetch */
            foreach ($node->attrGroups as $attributesGroupNode) {
                foreach ($attributesGroupNode->attrs as $attributeNode) {
                    if ($attributeNode->name->toString() !== 'JetBrains\PhpStorm\Internal\PhpStormStubsElementAvailable') {
                        continue;
                    }

                    foreach ($attributeNode->args as $attributeArg) {
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
            }
        }

        return [$fromVersion, $toVersion];
    }

    private function parsePhpVersion(string $version, int $defaultPatch = 0): int
    {
        $parts = array_map('intval', explode('.', $version));

        return $parts[0] * 10000 + $parts[1] * 100 + ($parts[2] ?? $defaultPatch);
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
