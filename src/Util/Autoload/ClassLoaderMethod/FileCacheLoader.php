<?php
declare(strict_types=1);

namespace Rector\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Rector\BetterReflection\Reflection\ReflectionClass;
use Rector\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;
use Rector\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Rector\Signature\CheckerInterface;
use Rector\Signature\Encoder\Sha1SumEncoder;
use Rector\Signature\FileContentChecker;
use Rector\Signature\FileContentSigner;
use Rector\Signature\SignerInterface;

final class FileCacheLoader implements LoaderMethodInterface
{
    /**
     * @var string
     */
    private $cacheDirectory;

    /**
     * @var ClassPrinterInterface
     */
    private $classPrinter;

    /**
     * @var SignerInterface
     */
    private $signer;

    /**
     * @var CheckerInterface
     */
    private $checker;

    /**
     * @param string $cacheDirectory
     * @param ClassPrinterInterface $classPrinter
     * @param SignerInterface $signer
     * @param CheckerInterface $checker
     */
    public function __construct(
        string $cacheDirectory,
        ClassPrinterInterface $classPrinter,
        SignerInterface $signer,
        CheckerInterface $checker
    ) {
        $this->cacheDirectory = $cacheDirectory;
        $this->classPrinter   = $classPrinter;
        $this->signer         = $signer;
        $this->checker        = $checker;
    }

    /**
     * {@inheritdoc}
     * @throws \Rector\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed
     */
    public function __invoke(ReflectionClass $classInfo) : void
    {
        $filename = $this->cacheDirectory . '/' . \sha1($classInfo->getName());

        if ( ! \file_exists($filename)) {
            $code = "<?php\n" . $this->classPrinter->__invoke($classInfo);
            \file_put_contents(
                $filename,
                \str_replace('<?php', "<?php\n// " . $this->signer->sign($code), $code)
            );
        }

        if ( ! $this->checker->check(\file_get_contents($filename))) {
            throw Exception\SignatureCheckFailed::fromReflectionClass($classInfo);
        }

        /** @noinspection PhpIncludeInspection */
        require_once $filename;
    }

    /**
     * @param string $cacheDirectory
     * @return self
     */
    public static function defaultFileCacheLoader(string $cacheDirectory) : self
    {
        return new self(
            $cacheDirectory,
            new PhpParserPrinter(),
            new FileContentSigner(new Sha1SumEncoder()),
            new FileContentChecker(new Sha1SumEncoder())
        );
    }
}
