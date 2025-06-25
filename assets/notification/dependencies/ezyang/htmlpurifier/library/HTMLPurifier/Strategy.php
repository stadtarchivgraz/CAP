<?php

/**
 * Supertype for classes that define a strategy for modifying/purifying tokens.
 *
 * While BracketSpace_Notification_Dependencies_HTMLPurifier's core purpose is fixing HTML into something proper,
 * strategies provide plug points for extra configuration or even extra
 * features, such as custom tags, custom parsing of text, etc.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */


abstract class BracketSpace_Notification_Dependencies_HTMLPurifier_Strategy
{

    /**
     * Executes the strategy on the tokens.
     *
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Token[] $tokens Array of BracketSpace_Notification_Dependencies_HTMLPurifier_Token objects to be operated on.
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token[] Processed array of token objects.
     */
    abstract public function execute($tokens, $config, $context);
}

// vim: et sw=4 sts=4
