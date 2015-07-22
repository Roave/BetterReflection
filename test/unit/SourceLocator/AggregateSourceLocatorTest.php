<?php

namespace BetterReflectionTest\SourceLocator;
use BetterReflection\Identifier\Identifier;
use BetterReflection\Identifier\IdentifierType;
use BetterReflection\SourceLocator\AggregateSourceLocator;
use BetterReflection\SourceLocator\LocatedSource;
use BetterReflection\SourceLocator\StringSourceLocator;

/**
 * @covers \BetterReflection\SourceLocator\AggregateSourceLocator
 */
class AggregateSourceLocatorTest extends \PHPUnit_Framework_TestCase
{
    public function testInvokeGenerator()
    {
        $inputLocators = [
            new StringSourceLocator('<?php source1'),
            new StringSourceLocator('<?php source2'),
        ];

        $aggregate = new AggregateSourceLocator($inputLocators);

        $identifier = new Identifier('Foo', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        /** @var \Generator $values */
        $values = $aggregate->__invoke($identifier);

        $values->rewind();
        $this->assertInstanceOf(LocatedSource::class, $values->current());
        $this->assertSame('<?php source1', $values->current()->getSource());

        $values->next();
        $this->assertInstanceOf(LocatedSource::class, $values->current());
        $this->assertSame('<?php source2', $values->current()->getSource());

        $values->next();
        $this->assertNull($values->current());
    }

    public function testNestedAggregate()
    {
        $nestedAggregate = new AggregateSourceLocator([
            new AggregateSourceLocator([
                new StringSourceLocator('<?php level2'),
                new AggregateSourceLocator([
                    new StringSourceLocator('<?php level3'),
                ]),
            ]),
            new StringSourceLocator('<?php level1')
        ]);

        $identifier = new Identifier('Level3', new IdentifierType(IdentifierType::IDENTIFIER_CLASS));

        /** @var \Generator $values */
        $values = $nestedAggregate->__invoke($identifier);

        $values->rewind();
        $this->assertInstanceOf(LocatedSource::class, $values->current());
        $this->assertSame('<?php level2', $values->current()->getSource());

        $values->next();
        $this->assertInstanceOf(LocatedSource::class, $values->current());
        $this->assertSame('<?php level3', $values->current()->getSource());

        $values->next();
        $this->assertInstanceOf(LocatedSource::class, $values->current());
        $this->assertSame('<?php level1', $values->current()->getSource());

        $values->next();
        $this->assertNull($values->current());
    }
}
