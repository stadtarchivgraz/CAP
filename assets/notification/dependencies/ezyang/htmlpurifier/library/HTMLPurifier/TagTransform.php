<?php

/**
 * Defines a mutation of an obsolete tag into a valid tag.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
abstract class BracketSpace_Notification_Dependencies_HTMLPurifier_TagTransform
{

    /**
     * Tag name to transform the tag to.
     * @type string
     */
    public $transform_to;

    /**
     * Transforms the obsolete tag into the valid tag.
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Token_Tag $tag Tag to be transformed.
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config Mandatory BracketSpace_Notification_Dependencies_HTMLPurifier_Config object
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context Mandatory BracketSpace_Notification_Dependencies_HTMLPurifier_Context object
     */
    abstract public function transform($tag, $config, $context);

    /**
     * Prepends CSS properties to the style attribute, creating the
     * attribute if it doesn't exist.
     * @warning Copied over from AttrTransform, be sure to keep in sync
     * @param array $attr Attribute array to process (passed by reference)
     * @param string $css CSS to prepend
     */
    protected function prependCSS(&$attr, $css)
    {
        $attr['style'] = isset($attr['style']) ? $attr['style'] : '';
        $attr['style'] = $css . $attr['style'];
    }
}

// vim: et sw=4 sts=4
