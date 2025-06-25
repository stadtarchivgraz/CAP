<?php

// must be called POST validation

/**
 * Adds target="blank" to all outbound links.  This transform is
 * only attached if Attr.TargetBlank is TRUE.  This works regardless
 * of whether or not Attr.AllowedFrameTargets
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_TargetBlank extends BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform
{
    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_URIParser
     */
    private $parser;

    public function __construct()
    {
        $this->parser = new BracketSpace_Notification_Dependencies_HTMLPurifier_URIParser();
    }

    /**
     * @param array $attr
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context
     * @return array
     */
    public function transform($attr, $config, $context)
    {
        if (!isset($attr['href'])) {
            return $attr;
        }

        // XXX Kind of inefficient
        $url = $this->parser->parse($attr['href']);
        
        // Ignore invalid schemes (e.g. `javascript:`)
        if (!($scheme = $url->getSchemeObj($config, $context))) {
            return $attr;
        }

        if ($scheme->browsable && !$url->isBenign($config, $context)) {
            $attr['target'] = '_blank';
        }
        return $attr;
    }
}

// vim: et sw=4 sts=4
