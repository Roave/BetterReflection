<?php

namespace BetterReflectionTest\Util\Autoload\ClassLoaderMethod;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Util\Autoload\ClassLoaderMethod\Exception\SignatureCheckFailed;
use BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;
use BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;
use Roave\Signature\CheckerInterface;
use Roave\Signature\SignerInterface;

/**
 * @covers \BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader
 */
class FileCacheLoaderTest extends \PHPUnit_Framework_TestCase
{
    public function testFileCacheWriterCreatesFileWithPrintedCode()
    {
        $className = uniqid(__METHOD__, true);
        $generatedFilename = __DIR__ . '/' . sha1($className);

        $classInfo = $this->createMock(ReflectionClass::class);
        $classInfo->expects(self::exactly(2))->method('getName')->willReturn($className);

        $generatedCode = '// ' . uniqid(__METHOD__, true);
        $signature = uniqid('Roave/Signature: ', true);
        $signedCode = '<?php // ' . $signature . "\n" . $generatedCode;

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($generatedCode);

        $signer = $this->createMock(SignerInterface::class);
        $signer->expects(self::once())->method('sign')->with($generatedCode)->willReturn($signature);

        $checker = $this->createMock(CheckerInterface::class);
        $checker->expects(self::exactly(2))->method('check')->with($signedCode)->willReturn(true);

        (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);

        self::assertSame(
            $signedCode,
            file_get_contents($generatedFilename)
        );

        (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);

        unlink($generatedFilename);
    }

    public function testExceptionThrownWhenSignatureFailedToVerify()
    {
        $className = uniqid(__METHOD__, true);
        $generatedFilename = __DIR__ . '/' . sha1($className);

        $classInfo = $this->createMock(ReflectionClass::class);
        $classInfo->expects(self::exactly(2))->method('getName')->willReturn($className);

        $generatedCode = '// ' . uniqid(__METHOD__, true);
        $signature = uniqid('Roave/Signature: ', true);
        $signedCode = '<?php // ' . $signature . "\n" . $generatedCode;

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($generatedCode);

        $signer = $this->createMock(SignerInterface::class);
        $signer->expects(self::once())->method('sign')->with($generatedCode)->willReturn($signature);

        $checker = $this->createMock(CheckerInterface::class);
        $checker->expects(self::once())->method('check')->with($signedCode)->willReturn(false);

        try {
            (new FileCacheLoader(__DIR__, $printer, $signer, $checker))->__invoke($classInfo);
            $this->fail('Expected exception did not occur: ' . SignatureCheckFailed::class);
        } catch (SignatureCheckFailed $signatureCheckFailed) {
            return;
        } finally {
            unlink($generatedFilename);
        }
    }
}
