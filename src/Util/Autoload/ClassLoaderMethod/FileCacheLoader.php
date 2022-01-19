<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Roave\Signature\CheckerInterface;
use Roave\Signature\Encoder\Sha1SumEncoder;
use Roave\Signature\FileContentChecker;
use Roave\Signature\FileContentSigner;
use Roave\Signature\SignerInterface;

use function file_get_contents;
use function file_put_contents;
use function is_file;
use function sha1;
use function str_replace;

/**
 * @deprecated
 *
 * @psalm-suppress DeprecatedClass, DeprecatedInterface
 */
final class FileCacheLoader implements LoaderMethodInterface
{
    public function __construct(
        private string $cacheDirectory,
        private ClassPrinterInterface $classPrinter,
        private SignerInterface $signer,
        private CheckerInterface $checker,
    ) {
    }

    /**
     * {@inheritdoc}
     *
     * @throws SignatureCheckFailed
     */
    public function __invoke(ReflectionClass $classInfo): void
    {
        $filename = $this->cacheDirectory . '/' . sha1($classInfo->getName());

        if (! is_file($filename)) {
            $code = "<?php\n" . $this->classPrinter->__invoke($classInfo);
            file_put_contents(
                $filename,
                str_replace('<?php', "<?php\n// " . $this->signer->sign($code), $code),
            );
        }

        if (! $this->checker->check(file_get_contents($filename))) {
            throw Exception\SignatureCheckFailed::fromReflectionClass($classInfo);
        }

        /** @noinspection PhpIncludeInspection */
        require_once $filename;
    }

    public static function defaultFileCacheLoader(string $cacheDirectory): self
    {
        return new self(
            $cacheDirectory,
            new PhpParserPrinter(),
            new FileContentSigner(new Sha1SumEncoder()),
            new FileContentChecker(new Sha1SumEncoder()),
        );
    }
}
