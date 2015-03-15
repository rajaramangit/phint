<?php
namespace Phint;

use PhpParser\Parser;
use PhpParser\Lexer;
use Phint\Chain\ChainFactory;

class Checker
{
	/**
	 * The parser instance.
	 *
	 * @var Parser
	 */
	protected $parser;

	/**
	 * The context wrapper instance.
	 *
	 * @var ContextWrapper
	 */
	protected $context;

	/**
	 * The error bag instance.
	 *
	 * @var ErrorBag
	 */
	protected $errors;

	/**
	 * The node traverser instance.
	 *
	 * @var NodeTraverser
	 */
	protected $traverser;

	public function __construct(
		Parser $parser = null,
		ContextWrapper $context = null,
		ErrorBag $errors = null,
		NodeTraverser $traverser = null
	) {
		$this->parser = $parser ?: new Parser(new Lexer);
		$this->context = $context ?: new ContextWrapper();
		$this->errors = $errors ?: new ErrorBag();
		$this->traverser = $traverser ?: new NodeTraverser();
	}

	public function makeChainFactory()
	{
		$visitorCollection = $this->traverser->getVisitorCollection();
		return new ChainFactory($this->parser, $visitorCollection,
			$this->context, $this->errors);
	}

	public function addVisitor($visitor, array $args = [])
	{
		if (is_string($visitor)) {
			$args = array_merge([$this->traverser, $this->context, $this->errors], $args);
			$refl = new \ReflectionClass($visitor);
			$visitor = $refl->newInstanceArgs($args);
		}
		if (! $visitor instanceof NodeVisitorInterface) {
			throw new \InvalidArgumentException('Visitor must be instance of NodeVisitorInterface');
		}
		$this->traverser->addVisitor($visitor);
	}

	public function addDefaultVisitors()
	{
		$this->addVisitor('Phint\Visitors\NamespaceVisitor');
		$this->addVisitor('Phint\Visitors\UseVisitor');
		$this->addVisitor('Phint\Visitors\ClassVisitor');
		$this->addVisitor('Phint\Visitors\ClassMethodVisitor');
		$this->addVisitor('Phint\Visitors\VariableVisitor');
		$this->addVisitor('Phint\Visitors\StaticVarVisitor');
		$this->addVisitor('Phint\Visitors\AssignVisitor');
		$this->addVisitor('Phint\Visitors\BooleanNotVisitor');
		$this->addVisitor('Phint\Visitors\BinaryOpVisitor');
		$this->addVisitor('Phint\Visitors\TernaryVisitor');
		$this->addVisitor('Phint\Visitors\InstanceofVisitor');
		$this->addVisitor('Phint\Visitors\StringWithVariableVisitor');
		$this->addVisitor('Phint\Visitors\StaticMethodCallVisitor');
		$this->addVisitor('Phint\Visitors\NewVisitor');
		$this->addVisitor('Phint\Visitors\FunctionCallVisitor');
		$this->addVisitor('Phint\Visitors\ReturnVisitor');
		$this->addVisitor('Phint\Visitors\IfVisitor');
		$this->addVisitor('Phint\Visitors\WhileVisitor');
		$this->addVisitor('Phint\Visitors\ForVisitor');
		$this->addVisitor('Phint\Visitors\ForeachVisitor');
		$this->addVisitor('Phint\Visitors\TryCatchVisitor');
		$this->addVisitor('Phint\Visitors\SwitchVisitor');
		$this->addVisitor('Phint\Visitors\ClosureVisitor');

		// these visitors should be considered to be made optional
		$this->addVisitor('Phint\Visitors\ChainVisitor', [$this->makeChainFactory()]);
	}

	public function check($path)
	{
		$this->errors->clear();
		$code = file_get_contents($path);
		$nodes = $this->parser->parse($code);
		$this->context->setFileName($path);
		$this->traverser->traverse($nodes);
	}

	public function getErrors()
	{
		return $this->errors->getAll();
	}
}
