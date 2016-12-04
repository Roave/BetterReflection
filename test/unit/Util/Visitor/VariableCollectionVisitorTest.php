<?php

namespace BetterReflectionTest\Util\Visitor;

use BetterReflection\Util\Visitor\ReturnNodeVisitor;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use BetterReflection\Util\Visitor\VariableCollectionVisitor;
use BetterReflection\Reflection\ReflectionClass;
use BetterReflectionTest\Util\Visitor\source;
use BetterReflection\Reflector\ClassReflector;
use phpDocumentor\Reflection\TypeResolver;
use BetterReflection\Reflection\ReflectionVariable;
use BetterReflection\SourceLocator\Type\StringSourceLocator;
use BetterReflection\SourceLocator\Type\PhpInternalSourceLocator;
use BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use BetterReflection\SourceLocator\Type\AutoloadSourceLocator;
use BetterReflection\SourceLocator\Type\EvaledCodeSourceLocator;
use BetterReflection\NodeCompiler\CompilerContext;

/**
 * @covers \BetterReflection\Util\Visitor\VariableCollectionVisitor
 *
 * TODO: 
 *
 *  - Closure tests
 *  - Invalid type resolutions: what happens when trying to access an invalid chain?
 *  - Static resolution
 *  - (new ClassOne)->foobar(); resolution
 *  - Function return types
 *     
 */
class VariableCollectionVisitorTest extends \PHPUnit_Framework_TestCase
{
    public function variableCollectionProvider()
    {
        return [
            'shouldResolveNewObjectAssignments' => [
                <<<'EOT'
public function foobar()
{
    $bar = new ClassOne();
}
EOT
                , [
                    [ 'bar', '\\' . Fixtures\ClassOne::class ],
                ],
            ],
            'shouldAssumeMixedTypeIfMethodCannotBeResolved' => [
                <<<'EOT'
public function foobar()
{
    $bar = $this->barfoo();
}

public function barfoo()
{
}
EOT
                , [
                    [ 'bar', 'mixed' ],
                ],
            ],
            'shouldAssumeMixedTypeIfPropertyCannotBeResolved' => [
                <<<'EOT'
private $foobar;

public function foobar()
{
    $string = $this->foobar;
}
EOT
                , [
                    [ 'string', 'mixed' ],
                ],
            ],
            'shouldIgnorePropertyFetchOnScalarTypes' => [
                <<<'EOT'
/**
 * @var string
 */
private $foobar;

public function foobar()
{
    $string = $this->foobar->barfoo;
}
EOT
                , [
                    [ 'string', 'mixed' ],
                ],
            ],
            'shouldAcceptScalarPropertyTypes' => [
                <<<'EOT'
/**
 * @var string
 */
private $foobar;

public function foobar()
{
    $string = $this->foobar;
}
EOT
                , [
                    [ 'string', 'string' ],
                ],
            ],
            'shouldInterpretArrayAccessesAsMixed' => [
                <<<'EOT'
public function foobar()
{
    $bar = [ 'foobar' => 'barfoo' ];
    $access = $bar['foobar'];
}
EOT
                , [
                    [ 'bar', 'array' ],
                    [ 'access', 'mixed' ],
                ],
            ],
            'shouldCollectScalarAssignations' => [
                <<<'EOT'
public function foobar()
{
    $float = 12.12;
    $integer = 1234;
    $string = '1234';
}
EOT
                , [
                    [ 'float', 'float' ],
                    [ 'integer', 'int' ],
                    [ 'string', 'string' ],
                ],
            ],
            'shouldCollectReassignedVariables' => [
                <<<'EOT'
public function foobar()
{
    $foo = 12;
    $foo = 'string';
}
EOT
                , [
                    [ 'foo', 'int' ],
                    [ 'foo', 'string' ],
                ],
            ],
            'shouldResolveCallsOnParameters' => [
                <<<'EOT'
public function foobar(ClassOne $params)
{
    $foo = $params->getClassThree();
}
EOT
                , [
                    [ 'params', '\\' . Fixtures\ClassOne::class ],
                    [ 'foo', '\\' . Fixtures\ClassThree::class ],
                ],
            ],
            'shouldResolveFunctionReturnTypes' => [
                <<<'EOT'
public function foobar()
{
    $name = trim('foobar');
}
EOT
                , [
                    [ 'name', 'mixed' ],
                ],
            ],
            'shouldResolveExtendedPropertyChain' => [
                <<<'EOT'
/**
 * @var ClassOne
 */
private $property;

public function methodOne()
{
    $object = $this->property->classTwo->classThree->classOne;
}
EOT
                , [
                    [ 'object', '\\' . Fixtures\ClassOne::class ],
                ],
            ],
            'shouldResolveExtendedMethodChain' => [
                <<<'EOT'
/**
 * @var ClassOne
 */
private $property;

public function methodOne()
{
    $object = $this->property->getClassTwo()->getClassThree()->getClassOne();
}
EOT
                , [
                    [ 'object', '\\' . Fixtures\ClassOne::class ],
                ],
            ],
            'shouldAcceptParameterWithNoTypeOrDocblock' => [
                <<<'EOT'
public function methodOne($param)
{
}
EOT
                , [
                    [ 'param', 'mixed' ],
                ],
            ],
            'shouldResolveMethodChain' => [
                <<<'EOT'
public function methodOne()
{
    $date = $this->me()->getClassOne();
}

public function getClassOne(): ClassOne
{
}

public function me(): Foobar
{
}
EOT
                , [
                    [ 'date', '\\' . Fixtures\ClassOne::class ],
                ],
            ],
            'shouldResolvePropertyChain' => [
                <<<'EOT'
/**
 * @var ClassOne
 */
private $object;

public function getClassThree()
{
    $date = $this->object->getClassThree();
}
EOT

                , [
                    [ 'date', '\\' . Fixtures\ClassThree::class ],
                ]
            ],
            'shouldResolveParams' => [
                <<<'EOT'
public function getClassThree(int $number, ClassOne $object)
{
}
EOT
                , [
                    [ 'number', 'int' ],
                    [ 'object', '\\' . Fixtures\ClassOne::class ],
                ]
            ],
            'shouldResolveParamsWithDocblock' => [
                <<<'EOT'
/**
 * @param int $number
 * @param ClassTwo $object
 */
public function getClassThree($number, $object)
{
}
EOT
                , [
                    [ 'number', 'int' ],
                    [ 'object', '\\' . Fixtures\ClassTwo::class ],
                ]
            ],
            'shouldResolveThis' => [
            <<<'EOT'
public function getClassThree()
{
    $foo = $this;
}
EOT
                , [
                    [ 'foo', '\\' . __NAMESPACE__ . '\\Fixtures\\Foobar' ]
                ]
            ],
        ];
    }

    /**
     * @param Node[] $statements
     * @param int $expectedReturns
     *
     * @dataProvider variableCollectionProvider
     */
    public function testVariableCollectionProvider($source, $expectedVariables)
    {
        $source = str_replace(':content:', $source, <<<'EOT'
<?php

namespace BetterReflectionTest\Util\Visitor\Fixtures;

class Foobar
{
:content:
}
EOT
        );

        $sourceLocator = new AggregateSourceLocator([
            new StringSourceLocator($source),
            new AutoloadSourceLocator(),
        ]);

        $reflector = new ClassReflector($sourceLocator);
        $reflection = $reflector->reflect('BetterReflectionTest\Util\Visitor\Fixtures\Foobar');
        $context = new CompilerContext($reflector, $reflection);

        $visitor = new VariableCollectionVisitor($context);

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);
        $traverser->traverse([ $reflection->getAst() ]);

        $variables = $visitor->getVariables();

        foreach ($expectedVariables as $index => $expectedVariable) {
            list($expectedName, $expectedType) = $expectedVariable;
            $this->assertArrayHasKey($index, $variables);
            $actual = $variables[$index];
            $this->assertInstanceOf(ReflectionVariable::class, $actual);
            $this->assertEquals($expectedName, $actual->getName());
            $this->assertEquals($expectedType, (string) $actual->getTypeObject());
        }
    }
}

