<?php

declare(strict_types=1);

namespace Rector\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

final class ReplaceCommandOutputStyleRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replaces the Laravel command console output with the modern components variant', [
            new CodeSample(
                <<<'CODE_SAMPLE'
$this->info('Message');
$this->warn('Message');
$this->error('Message');
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
$this->components->info('Message');
$this->components->warn('Message');
$this->components->error('Message');
CODE_SAMPLE
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [MethodCall::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof MethodCall) {
            return null;
        }

        if (! $this->isObjectType($node->var, new ObjectType('Illuminate\Console\Command'))) {
            return null;
        }

        if (! $this->isNames($node->name, ['info', 'error', 'warn'])) {
            return null;
        }

        $componentsFetch = new PropertyFetch($node->var, 'components');

        return new MethodCall($componentsFetch, $node->name, $node->args);
    }
}
