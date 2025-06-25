<?php

/**
 * Definition cache decorator class that saves all cache retrievals
 * to PHP's memory; good for unit tests or circumstances where
 * there are lots of configuration objects floating around.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator_Memory extends BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator
{
    /**
     * @type array
     */
    protected $definitions;

    /**
     * @type string
     */
    public $name = 'Memory';

    /**
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator_Memory
     */
    public function copy()
    {
        return new BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator_Memory();
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return mixed
     */
    public function add($def, $config)
    {
        $status = parent::add($def, $config);
        if ($status) {
            $this->definitions[$this->generateKey($config)] = $def;
        }
        return $status;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return mixed
     */
    public function set($def, $config)
    {
        $status = parent::set($def, $config);
        if ($status) {
            $this->definitions[$this->generateKey($config)] = $def;
        }
        return $status;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return mixed
     */
    public function replace($def, $config)
    {
        $status = parent::replace($def, $config);
        if ($status) {
            $this->definitions[$this->generateKey($config)] = $def;
        }
        return $status;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return mixed
     */
    public function get($config)
    {
        $key = $this->generateKey($config);
        if (isset($this->definitions[$key])) {
            return $this->definitions[$key];
        }
        $this->definitions[$key] = parent::get($config);
        return $this->definitions[$key];
    }
}

// vim: et sw=4 sts=4
