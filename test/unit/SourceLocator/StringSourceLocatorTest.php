<?php

namespace BetterReflectionTest\SourceLocator;

use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\StringSourceLocator;
use BetterReflection\SourceLocator\Exception\EmptyPhpSourceCode;

/**
 * @covers \BetterReflection\SourceLocator\StringSourceLocator
 */
class StringSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokableLoadsSource()
    {
        $sourceCode = '<?php echo "Hello world!";';

        $locator = new StringSourceLocator($sourceCode);

        $locatedSource = $locator->__invoke(new Identifier(
            'does not matter what the class name is',
            new IdentifierType(IdentifierType::IDENTIFIER_CLASS)
        ));

        $this->assertSame($sourceCode, $locatedSource->getSource());
        $this->assertNull($locatedSource->getFileName());
    }

    public function testConstructorThrowsExceptionIfEmptyStringGiven()
    {
        $this->setExpectedException(EmptyPhpSourceCode::class);
        new StringSourceLocator('');
    }
}
