<?php

/**
 * Factory for token generation.
 *
 * @note Doing some benchmarking indicates that the new operator is much
 *       slower than the clone operator (even discounting the cost of the
 *       constructor).  This class is for that optimization.
 *       Other then that, there's not much point as we don't
 *       maintain parallel BracketSpace_Notification_Dependencies_HTMLPurifier_Token hierarchies (the main reason why
 *       you'd want to use an abstract factory).
 * @todo Port DirectLex to use this
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_TokenFactory
{
    // p stands for prototype

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start
     */
    private $p_start;

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Token_End
     */
    private $p_end;

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Empty
     */
    private $p_empty;

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Text
     */
    private $p_text;

    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Comment
     */
    private $p_comment;

    /**
     * Generates blank prototypes for cloning.
     */
    public function __construct()
    {
        $this->p_start = new BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start('', array());
        $this->p_end = new BracketSpace_Notification_Dependencies_HTMLPurifier_Token_End('');
        $this->p_empty = new BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Empty('', array());
        $this->p_text = new BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Text('');
        $this->p_comment = new BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Comment('');
    }

    /**
     * Creates a BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start.
     * @param string $name Tag name
     * @param array $attr Associative array of attributes
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start Generated BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Start
     */
    public function createStart($name, $attr = array())
    {
        $p = clone $this->p_start;
        $p->__construct($name, $attr);
        return $p;
    }

    /**
     * Creates a BracketSpace_Notification_Dependencies_HTMLPurifier_Token_End.
     * @param string $name Tag name
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token_End Generated BracketSpace_Notification_Dependencies_HTMLPurifier_Token_End
     */
    public function createEnd($name)
    {
        $p = clone $this->p_end;
        $p->__construct($name);
        return $p;
    }

    /**
     * Creates a BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Empty.
     * @param string $name Tag name
     * @param array $attr Associative array of attributes
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Empty Generated BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Empty
     */
    public function createEmpty($name, $attr = array())
    {
        $p = clone $this->p_empty;
        $p->__construct($name, $attr);
        return $p;
    }

    /**
     * Creates a BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Text.
     * @param string $data Data of text token
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Text Generated BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Text
     */
    public function createText($data)
    {
        $p = clone $this->p_text;
        $p->__construct($data);
        return $p;
    }

    /**
     * Creates a BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Comment.
     * @param string $data Data of comment token
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Comment Generated BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Comment
     */
    public function createComment($data)
    {
        $p = clone $this->p_comment;
        $p->__construct($data);
        return $p;
    }
}

// vim: et sw=4 sts=4
