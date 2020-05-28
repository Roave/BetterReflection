<?php

declare(strict_types=1);

namespace Roave\BetterReflection\Reflection;

use Closure;
use LogicException;
use phpDocumentor\Reflection\Type;
use PhpParser\Comment\Doc;
use PhpParser\Node;
use PhpParser\Node\Expr\Yield_ as YieldNode;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param as ParamNode;
use PhpParser\Node\Stmt\Namespace_ as NamespaceNode;
use PhpParser\NodeTraverser;
use PhpParser\Parser;
use PhpParser\PrettyPrinter\Standard as StandardPrettyPrinter;
use PhpParser\PrettyPrinterAbstract;
use Roave\BetterReflection\BetterReflection;
use Roave\BetterReflection\Identifier\Exception\InvalidIdentifierName;
use Roave\BetterReflection\Identifier\Identifier;
use Roave\BetterReflection\Identifier\IdentifierType;
use Roave\BetterReflection\Reflection\Exception\InvalidAbstractFunctionNodeType;
use Roave\BetterReflection\Reflection\Exception\Uncloneable;
use Roave\BetterReflection\Reflector\Reflector;
use Roave\BetterReflection\SourceLocator\Ast\Exception\ParseToAstFailure;
use Roave\BetterReflection\SourceLocator\Located\LocatedSource;
use Roave\BetterReflection\SourceLocator\Type\ClosureSourceLocator;
use Roave\BetterReflection\TypesFinder\FindReturnType;
use Roave\BetterReflection\Util\CalculateReflectionColum;
use Roave\BetterReflection\Util\GetFirstDocComment;
use Roave\BetterReflection\Util\Visitor\ReturnNodeVisitor;
use function array_filter;
use function assert;
use function count;
use function implode;
use function is_array;
use function is_string;
use function strtolower;

abstract class ReflectionFunctionAbstract
{
    public const CLOSURE_NAME = '{closure}';

    /** @var NamespaceNode|null */
    private $declaringNamespace;

    /** @var LocatedSource */
    private $locatedSource;

    /** @var Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure|null */
    private $node;

    /** @var Reflector */
    private $reflector;

    /** @var Parser|null */
    private static $parser;

    protected function __construct()
    {
    }

    /**
     * Populate the common elements of the function abstract.
     *
     * @param Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\Expr\Closure $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     *
     * @throws InvalidAbstractFunctionNodeType
     */
    protected function populateFunctionAbstract(
        Reflector $reflector,
        Node\FunctionLike $node,
        LocatedSource $locatedSource,
        ?NamespaceNode $declaringNamespace = null
    ) : void {
        $this->reflector          = $reflector;
        $this->node               = $node;
        $this->locatedSource      = $locatedSource;
        $this->declaringNamespace = $declaringNamespace;

        $this->setNodeOptionalFlag();
    }

    /**
     * Get the AST node from which this function was created
     *
     * @return Node\Expr\Closure|Node\Stmt\ClassMethod|Node\Stmt\Function_
     */
    protected function getNode() : Node\FunctionLike
    {
        assert($this->node !== null);

        return $this->node;
    }

    /**
     * We must determine if params are optional or not ahead of time, but we
     * must do it in reverse...
     */
    private function setNodeOptionalFlag() : void
    {
        $overallOptionalFlag = true;
        $lastParamIndex      = count($this->getNode()->params) - 1;
        for ($i = $lastParamIndex; $i >= 0; $i--) {
            $hasDefault = ($this->getNode()->params[$i]->default !== null);

            // When we find the first parameter that does not have a default,
            // flip the flag as all params for this are no longer optional
            // EVEN if they have a default value
            if (! $hasDefault) {
                $overallOptionalFlag = false;
            }

            $this->getNode()->params[$i]->isOptional = $overallOptionalFlag;
        }
    }

    /**
     * Get the "full" name of the function (e.g. for A\B\foo, this will return
     * "A\B\foo").
     */
    public function getName() : string
    {
        if (! $this->inNamespace()) {
            return $this->getShortName();
        }

        return $this->getNamespaceName() . '\\' . $this->getShortName();
    }

    /**
     * Get the "short" name of the function (e.g. for A\B\foo, this will return
     * "foo").
     */
    public function getShortName() : string
    {
        $initializedNode = $this->getNode();
        if ($initializedNode instanceof Node\Expr\Closure) {
            return self::CLOSURE_NAME;
        }

        return $initializedNode->name->name;
    }

    /**
     * Get the "namespace" name of the function (e.g. for A\B\foo, this will
     * return "A\B").
     *
     * @psalm-suppress PossiblyNullPropertyFetch
     */
    public function getNamespaceName() : string
    {
        if (! $this->inNamespace()) {
            return '';
        }

        return implode('\\', $this->declaringNamespace->name->parts);
    }

    /**
     * Decide if this function is part of a namespace. Returns false if the class
     * is in the global namespace or does not have a specified namespace.
     */
    public function inNamespace() : bool
    {
        return $this->declaringNamespace !== null
            && $this->declaringNamespace->name !== null;
    }

    /**
     * Get the number of parameters for this class.
     */
    public function getNumberOfParameters() : int
    {
        return count($this->getParameters());
    }

    /**
     * Get the number of required parameters for this method.
     */
    public function getNumberOfRequiredParameters() : int
    {
        return count(array_filter(
            $this->getParameters(),
            static function (ReflectionParameter $p) : bool {
                return ! $p->isOptional();
            }
        ));
    }

    /**
     * Get an array list of the parameters for this method signature, as an
     * array of ReflectionParameter instances.
     *
     * @return list<ReflectionParameter>
     */
    public function getParameters() : array
    {
        $parameters = [];

        /** @var list<Node\Param> $nodeParams */
        $nodeParams = $this->getNode()->params;
        foreach ($nodeParams as $paramIndex => $paramNode) {
            $parameters[] = ReflectionParameter::createFromNode(
                $this->reflector,
                $paramNode,
                $this->declaringNamespace,
                $this,
                $paramIndex
            );
        }

        return $parameters;
    }

    /**
     * Get a single parameter by name. Returns null if parameter not found for
     * the function.
     */
    public function getParameter(string $parameterName) : ?ReflectionParameter
    {
        foreach ($this->getParameters() as $parameter) {
            if ($parameter->getName() === $parameterName) {
                return $parameter;
            }
        }

        return null;
    }

    public function getDocComment() : string
    {
        return GetFirstDocComment::forNode($this->getNode());
    }

    public function setDocCommentFromString(string $string) : void
    {
        $this->getAst()->setDocComment(new Doc($string));
    }

    public function getFileName() : ?string
    {
        return $this->locatedSource->getFileName();
    }

    public function getLocatedSource() : LocatedSource
    {
        return $this->locatedSource;
    }

    /**
     * Is this function a closure?
     */
    public function isClosure() : bool
    {
        return $this->node instanceof Node\Expr\Closure;
    }

    /**
     * Is this function deprecated?
     *
     * Note - we cannot reflect on internal functions (as there is no PHP source
     * code we can access. This means, at present, we can only EVER return false
     * from this function.
     *
     * @see https://github.com/Roave/BetterReflection/issues/38
     */
    public function isDeprecated() : bool
    {
        return false;
    }

    public function isInternal() : bool
    {
        return $this->locatedSource->isInternal();
    }

    /**
     * Is this a user-defined function (will always return the opposite of
     * whatever isInternal returns).
     */
    public function isUserDefined() : bool
    {
        return ! $this->isInternal();
    }

    public function getExtensionName() : ?string
    {
        return $this->locatedSource->getExtensionName();
    }

    /**
     * Check if the function has a variadic parameter.
     */
    public function isVariadic() : bool
    {
        $parameters = $this->getParameters();

        foreach ($parameters as $parameter) {
            if ($parameter->isVariadic()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively search an array of statements (PhpParser nodes) to find if a
     * yield expression exists anywhere (thus indicating this is a generator).
     */
    private function nodeIsOrContainsYield(Node $node) : bool
    {
        if ($node instanceof YieldNode) {
            return true;
        }

        foreach ($node->getSubNodeNames() as $nodeName) {
            $nodeProperty = $node->$nodeName;

            if ($nodeProperty instanceof Node && $this->nodeIsOrContainsYield($nodeProperty)) {
                return true;
            }

            if (! is_array($nodeProperty)) {
                continue;
            }

            foreach ($nodeProperty as $nodePropertyArrayItem) {
                if ($nodePropertyArrayItem instanceof Node && $this->nodeIsOrContainsYield($nodePropertyArrayItem)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if this function can be used as a generator (i.e. contains the
     * "yield" keyword).
     */
    public function isGenerator() : bool
    {
        if ($this->node === null) {
            return false;
        }

        return $this->nodeIsOrContainsYield($this->node);
    }

    /**
     * Get the line number that this function starts on.
     */
    public function getStartLine() : int
    {
        return $this->getNode()->getStartLine();
    }

    /**
     * Get the line number that this function ends on.
     */
    public function getEndLine() : int
    {
        return $this->getNode()->getEndLine();
    }

    public function getStartColumn() : int
    {
        return CalculateReflectionColum::getStartColumn($this->locatedSource->getSource(), $this->getNode());
    }

    public function getEndColumn() : int
    {
        return CalculateReflectionColum::getEndColumn($this->locatedSource->getSource(), $this->getNode());
    }

    /**
     * Is this function declared as a reference.
     */
    public function returnsReference() : bool
    {
        return $this->getNode()->byRef;
    }

    /**
     * Get the return types defined in the DocBlocks. This returns an array because
     * the parameter may have multiple (compound) types specified (for example
     * when you type hint pipe-separated "string|null", in which case this
     * would return an array of Type objects, one for string, one for null.
     *
     * @return Type[]
     */
    public function getDocBlockReturnTypes() : array
    {
        return (new FindReturnType())->__invoke($this, $this->declaringNamespace);
    }

    /**
     * Get the return type declaration (only for PHP 7+ code)
     */
    public function getReturnType() : ?ReflectionType
    {
        $returnType = $this->getNode()->getReturnType();

        if ($returnType === null) {
            return null;
        }

        if ($returnType instanceof NullableType) {
            return ReflectionType::createFromTypeAndReflector((string) $returnType->type, true, $this->reflector);
        }

        return ReflectionType::createFromTypeAndReflector((string) $returnType, false, $this->reflector);
    }

    /**
     * Do we have a return type declaration (only for PHP 7+ code)
     */
    public function hasReturnType() : bool
    {
        return $this->getReturnType() !== null;
    }

    /**
     * Set the return type declaration.
     */
    public function setReturnType(string $newReturnType) : void
    {
        $this->getNode()->returnType = new Node\Name($newReturnType);
    }

    /**
     * Remove the return type declaration completely.
     */
    public function removeReturnType() : void
    {
        $this->getNode()->returnType = null;
    }

    /**
     * @throws Uncloneable
     */
    public function __clone()
    {
        throw Uncloneable::fromClass(self::class);
    }

    /**
     * Retrieves the body of this function as AST nodes
     *
     * @return Node[]
     */
    public function getBodyAst() : array
    {
        return $this->getNode()->stmts ?: [];
    }

    /**
     * Retrieves the body of this function as code.
     *
     * If a PrettyPrinter is provided as a parameter, it will be used, otherwise
     * a default will be used.
     *
     * Note that the formatting of the code may not be the same as the original
     * function. If specific formatting is required, you should provide your
     * own implementation of a PrettyPrinter to unparse the AST.
     */
    public function getBodyCode(?PrettyPrinterAbstract $printer = null) : string
    {
        if ($printer === null) {
            $printer = new StandardPrettyPrinter();
        }

        return $printer->prettyPrint($this->getBodyAst());
    }

    /**
     * Fetch the AST for this method or function.
     *
     * @return Node\Stmt\ClassMethod|Node\Stmt\Function_|Node\FunctionLike
     */
    public function getAst() : Node\FunctionLike
    {
        return $this->getNode();
    }

    /**
     * Override the method or function's body of statements with an entirely new
     * body of statements within the reflection.
     *
     * @throws ParseToAstFailure
     * @throws InvalidIdentifierName
     *
     * @example
     * $reflectionFunction->setBodyFromClosure(function () { return true; });
     */
    public function setBodyFromClosure(Closure $newBody) : void
    {
        $closureReflection = (new ClosureSourceLocator($newBody, $this->loadStaticParser()))->locateIdentifier(
            $this->reflector,
            new Identifier(self::CLOSURE_NAME, new IdentifierType(IdentifierType::IDENTIFIER_FUNCTION))
        );
        assert($closureReflection instanceof self);

        $functionNode = $closureReflection->getNode();

        $this->getNode()->stmts = $functionNode->getStmts();
    }

    /**
     * Override the method or function's body of statements with an entirely new
     * body of statements within the reflection.
     *
     * @example
     * $reflectionFunction->setBodyFromString('return true;');
     */
    public function setBodyFromString(string $newBody) : void
    {
        $this->getNode()->stmts = $this->loadStaticParser()->parse('<?php ' . $newBody);
    }

    /**
     * Override the method or function's body of statements with an entirely new
     * body of statements within the reflection.
     *
     * @param Node[] $nodes
     *
     * @example
     * // $ast should be an array of Nodes
     * $reflectionFunction->setBodyFromAst($ast);
     */
    public function setBodyFromAst(array $nodes) : void
    {
        // This slightly confusing code simply type-checks the $sourceLocators
        // array by unpacking them and splatting them in the closure.
        $validator              = static function (Node ...$node) : array {
            return $node;
        };
        $this->getNode()->stmts = $validator(...$nodes);
    }

    /**
     * Add a new parameter to the method/function.
     */
    public function addParameter(string $parameterName) : void
    {
        $this->getNode()->params[] = new ParamNode(new Node\Expr\Variable($parameterName));
    }

    /**
     * Remove a parameter from the method/function.
     */
    public function removeParameter(string $parameterName) : void
    {
        $lowerName = strtolower($parameterName);

        foreach ($this->getNode()->params as $key => $paramNode) {
            if ($paramNode->var instanceof Node\Expr\Error) {
                throw new LogicException('PhpParser left an "Error" node in the parameters AST, this should NOT happen');
            }

            if (! is_string($paramNode->var->name) || strtolower($paramNode->var->name) !== $lowerName) {
                continue;
            }

            unset($this->getNode()->params[$key]);
        }
    }

    /**
     * Fetch an array of all return statements found within this function.
     *
     * Note that return statements within smaller scopes contained (e.g. anonymous classes, closures) are not returned
     * here as they are not within the immediate scope.
     *
     * @return Node\Stmt\Return_[]
     */
    public function getReturnStatementsAst() : array
    {
        $visitor = new ReturnNodeVisitor();

        $traverser = new NodeTraverser();
        $traverser->addVisitor($visitor);

        $traverser->traverse($this->getNode()->getStmts());

        return $visitor->getReturnNodes();
    }

    private function loadStaticParser() : Parser
    {
        return self::$parser ?? self::$parser = (new BetterReflection())->phpParser();
    }
}
