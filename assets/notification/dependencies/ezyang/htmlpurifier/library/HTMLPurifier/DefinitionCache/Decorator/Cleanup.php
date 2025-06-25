<?php

/**
 * Definition cache decorator class that cleans up the cache
 * whenever there is a cache miss.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator_Cleanup extends BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator
{
    /**
     * @type string
     */
    public $name = 'Cleanup';

    /**
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator_Cleanup
     */
    public function copy()
    {
        return new BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Decorator_Cleanup();
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return mixed
     */
    public function add($def, $config)
    {
        $status = parent::add($def, $config);
        if (!$status) {
            parent::cleanup($config);
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
        if (!$status) {
            parent::cleanup($config);
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
        if (!$status) {
            parent::cleanup($config);
        }
        return $status;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return mixed
     */
    public function get($config)
    {
        $ret = parent::get($config);
        if (!$ret) {
            parent::cleanup($config);
        }
        return $ret;
    }
}

// vim: et sw=4 sts=4
