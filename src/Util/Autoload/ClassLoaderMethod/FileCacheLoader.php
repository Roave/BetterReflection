<?php

namespace Roave\BetterReflection\Util\Autoload\ClassLoaderMethod;

use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\PhpParserPrinter;
use Roave\Signature\CheckerInterface;
use Roave\Signature\Encoder\Base64Encoder;
use Roave\Signature\FileContentChecker;
use Roave\Signature\FileContentSigner;
use Roave\Signature\Hasher\Md5Hasher;
use Roave\Signature\SignerInterface;

class FileCacheLoader implements LoaderMethodInterface
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
        $cacheDirectory,
        ClassPrinterInterface $classPrinter,
        SignerInterface $signer,
        CheckerInterface $checker
    ) {
        $this->cacheDirectory = $cacheDirectory;
        $this->classPrinter = $classPrinter;
        $this->signer = $signer;
        $this->checker = $checker;
    }

    /**
     * {@inheritdoc}
     * @throws \BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed
     */
    public function __invoke(ReflectionClass $classInfo)
    {
        $filename = $this->cacheDirectory . '/' . sha1($classInfo->getName());

        if (!file_exists($filename)) {
            $code = $this->classPrinter->__invoke($classInfo);
            file_put_contents(
                $filename,
                sprintf("<?php\n// %s\n%s", $this->signer->sign($code), $code)
            );
        }

        if (!$this->checker->check(file_get_contents($filename))) {
            throw Exception\SignatureCheckFailed::fromReflectionClass($classInfo);
        }

        /** @noinspection PhpIncludeInspection */
        require_once $filename;
    }

    public static function defaultFileCacheLoader($cacheDirectory)
    {
        return new self(
            $cacheDirectory,
            new PhpParserPrinter(),
            new FileContentSigner(new Base64Encoder(), new Md5Hasher()),
            new FileContentChecker(new Base64Encoder(), new Md5Hasher())
        );
    }
}
