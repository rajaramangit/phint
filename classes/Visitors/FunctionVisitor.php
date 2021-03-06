<?php
namespace Phint\Visitors;

use Phint\AbstractNodeVisitor;
use Phint\Error;
use Phint\NodeVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Function_;
use PhpParser\Node\Param;
use ReflectionFunction;

class FunctionVisitor extends AbstractNodeVisitor implements NodeVisitorInterface
{
	public function visit(Node $node)
	{
		if (! $node instanceof Function_) {
			return;
		}

		$ctx = $this->getContext();

		$ctx->resetVariables(false);
		$ctx->setFunctionNode($node);
		$ctx->setReflectionFunction(new ReflectionFunction($node->name));

		foreach ($node->params as $param) {
			if ($param->type instanceof Name) {
				$type = $ctx->getClassName($param->type);
				if (!$this->classExists($type)) {
					$this->addError($this->createClassNotFoundError(
						$node, $param, $type
					));
				}
			}

			$ctx->setVariable($param->name, $param);
		}

		$this->recurse($node->stmts);

		$ctx->setReflectionFunction(null);
	}

	private function createClassNotFoundError(Function_ $node, Param $param, $type)
	{
		$type = ltrim($type, '\\');
		$msg = "Function {$node->name} argument \${$param->name} is type-hinted "
			. "against a non-existant class: $type";
		return new Error($msg, $param);
	}
}
