<?php

/**
 * Registry object that contains information about the current context.
 * @warning Is a bit buggy when variables are set to null: it thinks
 *          they don't exist! So use false BracketSpace_Notification_Dependencies_instead, please.
 * @note Since the variables Context deals with may not be objects,
 *       references are very important here! Do not remove!
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_Context
{

    /**
     * Private array that stores the references.
     * @type array
     */
    private $_storage = array();

    /**
     * Registers a variable into the context.
     * @param string $name String name
     * @param mixed $ref Reference to variable to be registered
     */
    public function register($name, &$ref)
    {
        if (array_key_exists($name, $this->_storage)) {
            trigger_error(
                "Name $name produces collision, cannot re-register",
                E_USER_ERROR
            );
            return;
        }
        $this->_storage[$name] =& $ref;
    }

    /**
     * Retrieves a variable reference from the context.
     * @param string $name String name
     * @param bool $ignore_error Boolean whether or not to ignore error
     * @return mixed
     */
    public function &get($name, $ignore_error = false)
    {
        if (!array_key_exists($name, $this->_storage)) {
            if (!$ignore_error) {
                trigger_error(
                    "Attempted to retrieve non-existent variable $name",
                    E_USER_ERROR
                );
            }
            $var = null; // so we can return by reference
            return $var;
        }
        return $this->_storage[$name];
    }

    /**
     * Destroys a variable in the context.
     * @param string $name String name
     */
    public function destroy($name)
    {
        if (!array_key_exists($name, $this->_storage)) {
            trigger_error(
                "Attempted to destroy non-existent variable $name",
                E_USER_ERROR
            );
            return;
        }
        unset($this->_storage[$name]);
    }

    /**
     * Checks whether or not the variable exists.
     * @param string $name String name
     * @return bool
     */
    public function exists($name)
    {
        return array_key_exists($name, $this->_storage);
    }

    /**
     * Loads a series of variables from an associative array
     * @param array $context_array Assoc array of variables to load
     */
    public function loadArray($context_array)
    {
        foreach ($context_array as $key => $discard) {
            $this->register($key, $context_array[$key]);
        }
    }
}

// vim: et sw=4 sts=4
