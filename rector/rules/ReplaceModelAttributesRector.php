<?php

declare(strict_types=1);

namespace Rector\CustomRules;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Scalar\String_;
use PHPStan\Type\ObjectType;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Illuminate\Database\Eloquent\Model;

final class ReplaceModelAttributesRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Replace model attributes with $model->getAttribute("")', [
            new CodeSample(
                <<<'CODE_SAMPLE'
$model->attribute;
CODE_SAMPLE
                ,
                <<<'CODE_SAMPLE'
$model->getAttribute('attribute');
CODE_SAMPLE
            ),
        ]);
    }

    public function getNodeTypes(): array
    {
        return [PropertyFetch::class];
    }

    public function refactor(Node $node): ?Node
    {
        if (! $node instanceof PropertyFetch) {
            return null;
        }

        $objectType = $this->getType($node->var);
        if (! $objectType instanceof ObjectType || ! $objectType->isInstanceOf(Model::class)->yes()) {
            return null;
        }

        $attributeName = $this->getName($node->name);
        if ($attributeName === null) {
            return null;
        }

        return new MethodCall($node->var, 'getAttribute', [new Arg(new String_($attributeName))]);
    }
}
