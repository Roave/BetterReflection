<?php

namespace BetterReflectionTest\Util\Autoload;

use BetterReflection\Reflection\ReflectionClass;
use BetterReflection\Util\Autoload\ClassLoaderMethod\FileCacheLoader;
use BetterReflection\Util\Autoload\ClassPrinter\ClassPrinterInterface;

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

        $expectedContent = '<?php //' . uniqid(__METHOD__, true);

        $printer = $this->createMock(ClassPrinterInterface::class);
        $printer->expects(self::once())->method('__invoke')->with($classInfo)->willReturn($expectedContent);

        (new FileCacheLoader(__DIR__, $printer))->__invoke($classInfo);

        self::assertSame($expectedContent, file_get_contents($generatedFilename));

        (new FileCacheLoader(__DIR__, $printer))->__invoke($classInfo);

        unlink($generatedFilename);
    }
}
