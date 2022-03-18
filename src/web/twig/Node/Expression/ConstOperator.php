<?php

namespace Developion\Core\web\twig\Node\Expression;

use Exception;
use Twig\Compiler;
use Twig\Node\Expression\AbstractExpression;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

class ConstOperator extends AbstractExpression
{
	public function __construct(Node $left, Node $right, $lineno)
	{
		parent::__construct([
			'left' => $left,
			'right' => $right
		], array(), $lineno);
	}

	public function compile(Compiler $compiler)
	{
		if (!($this->getNode('right') instanceof NameExpression)) {
			throw new Exception("Right side of const operator must be identifier.");
		}

		$compiler
			->raw('(constant(get_class(')
			->subcompile($this->getNode('left'))
			->raw(') . (')
			->string("::" . $this->getNode('right')->getAttribute('name'))
			->raw(')))');
	}
}
