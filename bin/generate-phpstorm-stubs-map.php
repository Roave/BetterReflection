<?php

declare(strict_types=1);

namespace Roave\BetterReflection;

use DirectoryIterator;
use Exception;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;
use function array_map;
use function file_get_contents;
use function file_put_contents;
use function in_array;
use function is_file;
use function is_readable;
use function ksort;
use function preg_match;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;
use function var_export;

(function () : void {
    require __DIR__ . '/../vendor/autoload.php';

    $phpStormStubsDirectory = __DIR__ . '/../vendor/jetbrains/phpstorm-stubs';

    $phpParser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

    $fileVisitor = new class() extends NodeVisitorAbstract
    {
        /** @var string[] */
        private $classNames = [];

        /** @var string[] */
        private $functionNames = [];

        public function enterNode(Node $node) : ?int
        {
            if ($node instanceof Node\Stmt\ClassLike) {
                $this->classNames[] = $node->namespacedName->toString();

                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            if ($node instanceof Node\Stmt\Function_) {
                /** @psalm-suppress UndefinedPropertyFetch */
                $this->functionNames[] = $node->namespacedName->toString();

                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }

            return null;
        }

        /**
         * @return string[]
         */
        public function getClassNames() : array
        {
            return $this->classNames;
        }

        /**
         * @return string[]
         */
        public function getFunctionNames() : array
        {
            return $this->functionNames;
        }

        public function clear() : void
        {
            $this->classNames    = [];
            $this->functionNames = [];
        }
    };

    $nodeTraverser = new NodeTraverser();
    $nodeTraverser->addVisitor(new NameResolver());
    $nodeTraverser->addVisitor($fileVisitor);

    $map = ['classes' => [], 'functions' => []];

    foreach (new DirectoryIterator($phpStormStubsDirectory) as $directoryInfo) {
        if ($directoryInfo->isDot()) {
            continue;
        }

        if (! $directoryInfo->isDir()) {
            continue;
        }

        if (in_array($directoryInfo->getBasename(), ['tests', 'meta'], true)) {
            continue;
        }

        foreach (new DirectoryIterator($directoryInfo->getPathName()) as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            }

            if (! preg_match('/\.php$/', $fileInfo->getBasename())) {
                continue;
            }

            if (! is_file($fileInfo->getPathname())) {
                throw new Exception(sprintf('"%s" is not a file', $fileInfo->getPathname()));
            }

            if (! is_readable($fileInfo->getPathname())) {
                throw new Exception(sprintf('File "%s" is not readable', $fileInfo->getPathname()));
            }

            $ast = $phpParser->parse(file_get_contents($fileInfo->getPathname()));

            $nodeTraverser->traverse($ast);

            /** @psalm-suppress UndefinedMethod */
            foreach ($fileVisitor->getClassNames() as $className) {
                $map['classes'][$className] = $fileInfo->getPathname();
            }

            /** @psalm-suppress UndefinedMethod */
            foreach ($fileVisitor->getFunctionNames() as $functionName) {
                $map['functions'][$functionName] = $fileInfo->getPathname();
            }

            $fileVisitor->clear();
        }
    }

    $mapWithRelativeFilePaths = array_map(static function (array $files) use ($phpStormStubsDirectory) : array {
        ksort($files);

        return array_map(static function (string $filePath) use ($phpStormStubsDirectory) : string {
            return str_replace('\\', '/', substr($filePath, strlen($phpStormStubsDirectory) + 1));
        }, $files);
    }, $map);

    $exportedClasses   = var_export($mapWithRelativeFilePaths['classes'], true);
    $exportedFunctions = var_export($mapWithRelativeFilePaths['functions'], true);

    $output = <<<"PHP"
<?php

declare(strict_types=1);

namespace Roave\BetterReflection\SourceLocator\SourceStubber;

/**
 * @internal
 */
final class PhpStormStubsMap
{
const CLASSES = {$exportedClasses};

const FUNCTIONS = {$exportedFunctions};
}
PHP;

    $mapFile = __DIR__ . '/../src/SourceLocator/SourceStubber/PhpStormStubsMap.php';

    $bytesWritten = @file_put_contents($mapFile, $output);
    if ($bytesWritten === false) {
        throw new Exception(sprintf('File "%s" is not writeable.', $mapFile));
    }

    exit('Done');
})();
