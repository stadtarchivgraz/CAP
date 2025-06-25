<?php

/**
 * Represents a directive ID in the interchange format.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_ConfigSchema_Interchange_Id
{

    /**
     * @type string
     */
    public $key;

    /**
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @return string
     * @warning This is NOT magic, to ensure that people don't abuse SPL and
     *          cause problems for PHP 5.0 support.
     */
    public function toString()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getRootNamespace()
    {
        return substr($this->key, 0, strpos($this->key, "."));
    }

    /**
     * @return string
     */
    public function getDirective()
    {
        return substr($this->key, strpos($this->key, ".") + 1);
    }

    /**
     * @param string $id
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_ConfigSchema_Interchange_Id
     */
    public static function make($id)
    {
        return new BracketSpace_Notification_Dependencies_HTMLPurifier_ConfigSchema_Interchange_Id($id);
    }
}

// vim: et sw=4 sts=4
