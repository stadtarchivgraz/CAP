<?php

/**
 * Processes an entire attribute array for corrections needing multiple values.
 *
 * Occasionally, a certain attribute will need to be removed and popped onto
 * another value.  Instead of creating a complex return syntax for
 * BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef, we just pass the whole attribute array to a
 * specialized object and have that do the special work.  That is the
 * family of BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform.
 *
 * An attribute transformation can be assigned to run before or after
 * BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef validation.  See BracketSpace_Notification_Dependencies_HTMLPurifier_HTMLDefinition for
 * more details.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

abstract class BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform
{

    /**
     * Abstract: makes changes to the attributes dependent on multiple values.
     *
     * @param array $attr Assoc array of attributes, usually from
     *              BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Tag::$attr
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config Mandatory BracketSpace_Notification_Dependencies_HTMLPurifier_Config object.
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context Mandatory BracketSpace_Notification_Dependencies_HTMLPurifier_Context object
     * @return array Processed attribute array.
     */
    abstract public function transform($attr, $config, $context);

    /**
     * Prepends CSS properties to the style attribute, creating the
     * attribute if it doesn't exist.
     * @param array &$attr Attribute array to process (passed by reference)
     * @param string $css CSS to prepend
     */
    public function prependCSS(&$attr, $css)
    {
        $attr['style'] = isset($attr['style']) ? $attr['style'] : '';
        $attr['style'] = $css . $attr['style'];
    }

    /**
     * Retrieves and removes an attribute
     * @param array &$attr Attribute array to process (passed by reference)
     * @param mixed $key Key of attribute to confiscate
     * @return mixed
     */
    public function confiscateAttr(&$attr, $key)
    {
        if (!isset($attr[$key])) {
            return null;
        }
        $value = $attr[$key];
        unset($attr[$key]);
        return $value;
    }
}

// vim: et sw=4 sts=4
