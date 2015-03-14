<?php
namespace Phint\Visitors;

use Phint\AbstractNodeVisitor;
use Phint\Error;
use Phint\NodeVisitorInterface;
use ReflectionClass;
use PhpParser\Node;
use PhpParser\Node\Stmt\TryCatch;
use PhpParser\Node\Stmt\Catch_;

class TryCatchVisitor extends AbstractNodeVisitor implements NodeVisitorInterface
{
	public function visit(Node $node)
	{
		if (! $node instanceof TryCatch) {
			return;
		}

		$this->recurse($node->stmts);

		foreach ($node->catches as $catch) {
			if ($catch->type) {
				$className = $this->getContext()
					->getClassName($catch->type);

				if (!$this->classExists($className)) {
					$this->addError($this->createClassNotFoundError(
						$className, $catch->type));
				} elseif ($className !== 'Exception') {
					$refl = new ReflectionClass($className);
					if (!$refl->isSubclassOf('Exception') && !$refl->isInterface()) {
						$this->addError($this->createClassNotExceptionError(
							$className, $catch->type));
					}
				}
			}

			$newContext = $this->cloneContext();
			$newContext->setVariable($catch->var, $catch);

			$this->recurseWithNewContext($newContext, $catch->stmts);
		}

		if ($node->finallyStmts) {
			$this->recurse($node->finallyStmts);
		}
	}

	private function classExists($className)
	{
		return class_exists($className) || interface_exists($className);
	}

	private function createClassNotFoundError($className, Node $node)
	{
		$className = ltrim($className, '\\');
		$msg = "Trying to catch non-existant class: $className";
		return new Error($msg, $node);
	}

	private function createClassNotExceptionError($className, Node $node)
	{
		$className = ltrim($className, '\\');
		$msg = "Cannot catch class that is not an exception: $className";
		return new Error($msg, $node);
	}
}
