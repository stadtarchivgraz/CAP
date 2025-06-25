<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */ declare(strict_types=1);

namespace BracketSpace\Notification\Dependencies\PhpParser\Node\Expr\BinaryOp;

use BracketSpace\Notification\Dependencies\PhpParser\Node\Expr\BinaryOp;

class Spaceship extends BinaryOp
{
    public function getOperatorSigil() : string {
        return '<=>';
    }
    
    public function getType() : string {
        return 'Expr_BinaryOp_Spaceship';
    }
}
