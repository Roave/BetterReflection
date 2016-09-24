<?php
/**
 * Created by PhpStorm.
 * User: HP
 * Date: 09/24/2016
 * Time: 5:44 AM
 */

namespace BetterReflectionTest\SourceLocator\Type;


use BetterReflection\Reflector\ClassReflector;
use BetterReflection\SourceLocator\Type\DirectorySourceLocator;

class DirectorySourceLocatorTest extends \PHPUnit_Framework_TestCase
{


    public function testUtilDirectory(){
        $directoryToScan = __DIR__.'/../../Assets/DirectoryScannerAssets';
        $sourceLocator = new DirectorySourceLocator([$directoryToScan]);
        $reflector = new ClassReflector($sourceLocator);
        $this->assertEquals(2, count($reflector->getAllClasses()));
    }


}