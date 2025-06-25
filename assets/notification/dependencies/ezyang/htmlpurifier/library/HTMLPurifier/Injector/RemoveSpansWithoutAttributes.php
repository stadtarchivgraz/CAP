<?php

/**
 * Injector that removes spans with no attributes
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_Injector_RemoveSpansWithoutAttributes extends BracketSpace_Notification_Dependencies_HTMLPurifier_Injector
{
    /**
     * @type string
     */
    public $name = 'RemoveSpansWithoutAttributes';

    /**
     * @type array
     */
    public $needed = array('span');

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_AttrValidator
     */
    private $attrValidator;

    /**
     * Used by AttrValidator.
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Config
     */
    private $config;

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Context
     */
    private $context;

    /**
     * @type SplObjectStorage
     */
    private $markForDeletion;

    public function __construct()
    {
        $this->markForDeletion = new SplObjectStorage();
    }

    public function prepare($config, $context)
    {
        $this->attrValidator = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrValidator();
        $this->config = $config;
        $this->context = $context;
        return parent::prepare($config, $context);
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Token $token
     */
    public function handleElement(&$token)
    {
        if ($token->name !== 'span' || !$token instanceof BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start) {
            return;
        }

        // We need to validate the attributes now since this doesn't normally
        // happen until after MakeWellFormed. If all the attributes are removed
        // the span needs to be removed too.
        $this->attrValidator->validateToken($token, $this->config, $this->context);
        $token->armor['ValidateAttributes'] = true;

        if (!empty($token->attr)) {
            return;
        }

        $nesting = 0;
        while ($this->forwardUntilEndToken($i, $current, $nesting)) {
        }

        if ($current instanceof BracketSpace_Notification_Dependencies_HTMLPurifier_Token_End && $current->name === 'span') {
            // Mark closing span tag for deletion
            $this->markForDeletion->attach($current);
            // Delete open span tag
            $token = false;
        }
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Token $token
     */
    public function handleEnd(&$token)
    {
        if ($this->markForDeletion->contains($token)) {
            $this->markForDeletion->detach($token);
            $token = false;
        }
    }
}

// vim: et sw=4 sts=4
