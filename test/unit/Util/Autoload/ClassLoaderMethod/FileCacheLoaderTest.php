<?php

declare(strict_types=1);

namespace Roave\BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use PHPUnit\Framework\TestCase;
use Roave\BetterReflection\Reflection\ReflectionClass;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed;
use Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;
use Roave\BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;
use Roave\Signature\CheckerInterface;
use Roave\Signature\SignerInterface;

/**
 * @covers \Roave\BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader
 */
final class FileCacheLoaderTest extends TestCase
{
    public function testFileCacheWriterCreatesFileWithPrintedCode() : void
    {
        $className         = \uniqid(__METHOD__, true);
        $generatedFilename = __DIR__ . '/' . \sha1($className);

        /** @var ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $classInfo */
        $classInfo = $this->createMock(ReflectionClass::class);
        $classInfo->expects(self::exactly(2))->method('getName')->willReturn($className);

        $generatedCode = '// ' . \uniqid(__METHOD__, true);
        $signature     = \uniqid('Roave/Signature: ', true);
        $signedCode    = "<?php\n// " . $signature . "\n" . $generatedCode;

        /** @var ClassPrinterInterface|\PHPUnit_Framework_MockObject_MockObject $printer */
        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($generatedCode);

        /** @var SignerInterface|\PHPUnit_Framework_MockObject_MockObject $signer */
        $signer = $this->createMock(SignerInterface::class);
        $signer->expects(self::once())->method('sign')->with("<?php\n" . $generatedCode)->willReturn($signature);

        /** @var CheckerInterface|\PHPUnit_Framework_MockObject_MockObject $checker */
        $checker = $this->createMock(CheckerInterface::class);
        $checker->expects(self::exactly(2))->method('check')->with($signedCode)->willReturn(true);

        (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);

        self::assertStringEqualsFile($generatedFilename, $signedCode);

        (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);

        \unlink($generatedFilename);
    }

    public function testExceptionThrownWhenSignatureFailedToVerify() : void
    {
        $className         = \uniqid(__METHOD__, true);
        $generatedFilename = __DIR__ . '/' . \sha1($className);

        /** @var ReflectionClass|\PHPUnit_Framework_MockObject_MockObject $classInfo */
        $classInfo = $this->createMock(ReflectionClass::class);
        $classInfo->expects(self::exactly(2))->method('getName')->willReturn($className);

        $generatedCode = '// ' . \uniqid(__METHOD__, true);
        $signature     = \uniqid('Roave/Signature: ', true);
        $signedCode    = "<?php\n// " . $signature . "\n" . $generatedCode;

        /** @var ClassPrinterInterface|\PHPUnit_Framework_MockObject_MockObject $printer */
        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($generatedCode);

        /** @var SignerInterface|\PHPUnit_Framework_MockObject_MockObject $signer */
        $signer = $this->createMock(SignerInterface::class);
        $signer->expects(self::once())->method('sign')->with("<?php\n" . $generatedCode)->willReturn($signature);

        /** @var CheckerInterface|\PHPUnit_Framework_MockObject_MockObject $checker */
        $checker = $this->createMock(CheckerInterface::class);
        $checker->expects(self::once())->method('check')->with($signedCode)->willReturn(false);

        try {
            (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);
            self::fail('Expected exception did not occur: ' . SignatureCheckFailed::class);
        } catch (SignatureCheckFailed $signatureCheckFailed) {
            return;
        } finally {
            if (\file_exists($generatedFilename)) {
                \unlink($generatedFilename);
            }
        }
    }

    public function testDefaultFileCacheLoader() : void
    {
        $default = FileCacheLoader::defaultFileCacheLoader(__DIR__);
        self::assertInstanceOf(FileCacheLoader::class, $default);
    }
}
