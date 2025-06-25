<?php

/**
 * Validate all attributes in the tokens.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class BracketSpace_Notification_Dependencies_HTMLPurifier_Strategy_ValidateAttributes extends BracketSpace_Notification_Dependencies_HTMLPurifier_Strategy
{

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Token[] $tokens
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token[]
     */
    public function execute($tokens, $config, $context)
    {
        // setup validator
        $validator = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrValidator();

        $token = false;
        $context->register('CurrentToken', $token);

        foreach ($tokens as $key => $token) {

            // only process tokens that have attributes,
            //   namely start and empty tags
            if (!$token instanceof BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start && !$token instanceof BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Empty) {
                continue;
            }

            // skip tokens that are armored
            if (!empty($token->armor['ValidateAttributes'])) {
                continue;
            }

            // note that we have no facilities here for removing tokens
            $validator->validateToken($token, $config, $context);
        }
        $context->destroy('CurrentToken');
        return $tokens;
    }
}

// vim: et sw=4 sts=4
