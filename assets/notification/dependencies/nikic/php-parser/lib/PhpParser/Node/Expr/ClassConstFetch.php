<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\PhpParser\Node\Expr;

use BracketSpace\Notification\Dependencies\PhpParser\Node\Expr;
use BracketSpace\Notification\Dependencies\PhpParser\Node\Identifier;
use BracketSpace\Notification\Dependencies\PhpParser\Node\Name;

class ClassConstFetch extends Expr
{
    /** @var Name|Expr Class name */
    public $class;
    /** @var Identifier|Expr|Error Constant name */
    public $name;

    /**
     * Constructs a class const fetch node.
     *
     * @param Name|Expr                    $class      Class name
     * @param string|Identifier|Expr|Error $name       Constant name
     * @param array                        $attributes Additional attributes
     */
    public function __construct($class, $name, array $attributes = []) {
        $this->attributes = $attributes;
        $this->class = $class;
        $this->name = \is_string($name) ? new Identifier($name) : $name;
    }

    public function getSubNodeNames() : array {
        return ['class', 'name'];
    }

    public function getType() : string {
        return 'Expr_ClassConstFetch';
    }
}
