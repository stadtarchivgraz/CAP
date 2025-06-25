<?php

/*

WARNING: THIS MODULE IS EXTREMELY DANGEROUS AS IT ENABLES INLINE SCRIPTING
INSIDE HTML PURIFIER DOCUMENTS. USE ONLY WITH TRUSTED USER INPUT!!!

*
@license LGPL-2.1-or-later
Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
*/

/**
 * XHTML 1.1 Scripting module, defines elements that are used to contain
 * information pertaining to executable scripts or the lack of support
 * for executable scripts.
 * @note This module does not contain inline scripting elements
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_HTMLModule_Scripting extends BracketSpace_Notification_Dependencies_HTMLPurifier_HTMLModule
{
    /**
     * @type string
     */
    public $name = 'Scripting';

    /**
     * @type array
     */
    public $elements = array('script', 'noscript');

    /**
     * @type array
     */
    public $content_sets = array('Block' => 'script | noscript', 'Inline' => 'script | noscript');

    /**
     * @type bool
     */
    public $safe = false;

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     */
    public function setup($config)
    {
        // TODO: create custom child-definition for noscript that
        // auto-wraps stray #PCDATA in a similar manner to
        // blockquote's custom definition (we would use it but
        // blockquote's contents are optional while noscript's contents
        // are required)

        // TODO: convert this to new syntax, main problem is getting
        // both content sets working

        // In theory, this could be safe, but I don't see any reason to
        // allow it.
        $this->info['noscript'] = new BracketSpace_Notification_Dependencies_HTMLPurifier_ElementDef();
        $this->info['noscript']->attr = array(0 => array('Common'));
        $this->info['noscript']->content_model = 'Heading | List | Block';
        $this->info['noscript']->content_model_type = 'required';

        $this->info['script'] = new BracketSpace_Notification_Dependencies_HTMLPurifier_ElementDef();
        $this->info['script']->attr = array(
            'defer' => new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef_Enum(array('defer')),
            'src' => new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef_URI(true),
            'type' => new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef_Enum(array('text/javascript'))
        );
        $this->info['script']->content_model = '#PCDATA';
        $this->info['script']->content_model_type = 'optional';
        $this->info['script']->attr_transform_pre[] =
        $this->info['script']->attr_transform_post[] =
            new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_ScriptRequired();
    }
}

// vim: et sw=4 sts=4
