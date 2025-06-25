<?php

/**
 * Null cache object to use when no caching is on.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache_Null extends BracketSpace_Notification_Dependencies_HTMLPurifier_DefinitionCache
{

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function add($def, $config)
    {
        return false;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function set($def, $config)
    {
        return false;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Definition $def
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function replace($def, $config)
    {
        return false;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function remove($config)
    {
        return false;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function get($config)
    {
        return false;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function flush($config)
    {
        return false;
    }

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return bool
     */
    public function cleanup($config)
    {
        return false;
    }
}

// vim: et sw=4 sts=4
