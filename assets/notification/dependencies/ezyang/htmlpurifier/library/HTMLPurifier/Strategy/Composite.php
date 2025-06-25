<?php

/**
 * Composite strategy that runs multiple strategies on tokens.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
abstract class BracketSpace_Notification_Dependencies_HTMLPurifier_Strategy_Composite extends BracketSpace_Notification_Dependencies_HTMLPurifier_Strategy
{

    /**
     * List of strategies to run tokens through.
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Strategy[]
     */
    protected $strategies = array();

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Token[] $tokens
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token[]
     */
    public function execute($tokens, $config, $context)
    {
        foreach ($this->strategies as $strategy) {
            $tokens = $strategy->execute($tokens, $config, $context);
        }
        return $tokens;
    }
}

// vim: et sw=4 sts=4
