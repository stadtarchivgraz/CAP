<?php

/**
 * Registry for retrieving specific URI scheme validator objects.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_URISchemeRegistry
{

    /**
     * Retrieve sole instance of the registry.
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_URISchemeRegistry $prototype Optional prototype to overload sole instance with,
     *                   or bool true to reset to default registry.
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_URISchemeRegistry
     * @note Pass a registry object $prototype with a compatible interface and
     *       the function will copy it and return it all further times.
     */
    public static function instance($prototype = null)
    {
        static $instance = null;
        if ($prototype !== null) {
            $instance = $prototype;
        } elseif ($instance === null || $prototype == true) {
            $instance = new BracketSpace_Notification_Dependencies_HTMLPurifier_URISchemeRegistry();
        }
        return $instance;
    }

    /**
     * Cache of retrieved schemes.
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_URIScheme[]
     */
    protected $schemes = array();

    /**
     * Retrieves a scheme validator object
     * @param string $scheme String scheme name like http or mailto
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_URIScheme
     */
    public function getScheme($scheme, $config, $context)
    {
        if (!$config) {
            $config = BracketSpace_Notification_Dependencies_HTMLPurifier_Config::createDefault();
        }

        // important, otherwise attacker could include arbitrary file
        $allowed_schemes = $config->get('URI.AllowedSchemes');
        if (!$config->get('URI.OverrideAllowedSchemes') &&
            !isset($allowed_schemes[$scheme])
        ) {
            return;
        }

        if (isset($this->schemes[$scheme])) {
            return $this->schemes[$scheme];
        }
        if (!isset($allowed_schemes[$scheme])) {
            return;
        }

        $class = 'HTMLPurifier_URIScheme_' . $scheme;
        if (!class_exists($class)) {
            return;
        }
        $this->schemes[$scheme] = new $class();
        return $this->schemes[$scheme];
    }

    /**
     * Registers a custom scheme to the cache, bypassing reflection.
     * @param string $scheme Scheme name
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_URIScheme $scheme_obj
     */
    public function register($scheme, $scheme_obj)
    {
        $this->schemes[$scheme] = $scheme_obj;
    }
}

// vim: et sw=4 sts=4
