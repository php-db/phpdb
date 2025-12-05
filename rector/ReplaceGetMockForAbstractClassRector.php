<?php

declare(strict_types=1);

namespace CustomRule\PHPUnit;

use Override;
use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Variable;
use Rector\PhpParser\Node\Value\ValueResolver;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\Exception\PoorDocumentationException;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

class ReplaceGetMockForAbstractClassRector extends AbstractRector
{
    public function __construct(
        private readonly ValueResolver $valueResolver
    )
    {
    }

    /**
     * @throws PoorDocumentationException
     */
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Replace $this->getMockForAbstractClass() with $this->createMock() in PHPUnit tests',
            [
                new CodeSample(
                    '$this->getMockForAbstractClass(SomeClass::class);',
                    '$this->createMock(SomeClass::class);'
                ),
            ]
        );
    }

    #[Override] public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    #[Override] public function refactor(Node $node): ?Node
    {
        if (! $this->isName($node->name, 'getMockForAbstractClass') || ! $this->isName($node->var, 'this')) {
            return null;
        }

        $args = $node->args;
        if (count($args) < 1) {
            return null;
        }

        $builderCall = $this->nodeFactory->createMethodCall(
            new Variable('this'),
            'getMockBuilder',
            [new Arg($args[0]->value)]
        );

        $currentCall = $builderCall;

        // Derive constructor arguments
        if (isset($args[1])) {
            $currentCall = new MethodCall($currentCall, 'setConstructorArgs', [new Arg($args[1]->value)]);
        }

        // Derive methods
        $methodArgs = (isset($args[6])) ? $args[6]->value : new Node\Expr\Array_();
        $currentCall = new MethodCall($currentCall, 'onlyMethods', [new Arg($methodArgs)]);

        // Original constructor flag (false -> disable)
        if (isset($args[3]) && $this->valueResolver->isFalse($args[3]->value)) {
            $currentCall = new MethodCall($currentCall, 'disableOriginalConstructor');
        }

        return new MethodCall($currentCall, 'getMock');
    }
}
