<?php

/**
 * @file
 * Defines a function wrapper for HTML Purifier for quick use.
 * @note ''BracketSpace_Notification_Dependencies_HTMLPurifier()'' is NOT the same as ''new BracketSpace_Notification_Dependencies_HTMLPurifier()''
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

/**
 * Purify HTML.
 * @param string $html String HTML to purify
 * @param mixed $config Configuration to use, can be any value accepted by
 *        BracketSpace_Notification_Dependencies_HTMLPurifier_Config::create()
 * @return string
 */
function BracketSpace_Notification_Dependencies_HTMLPurifier($html, $config = null)
{
    static $purifier = false;
    if (!$purifier) {
        $purifier = new BracketSpace_Notification_Dependencies_HTMLPurifier();
    }
    return $purifier->purify($html, $config);
}

// vim: et sw=4 sts=4
