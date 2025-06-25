<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class BracketSpace_Notification_Dependencies_HTMLPurifier_Printer_CSSDefinition extends BracketSpace_Notification_Dependencies_HTMLPurifier_Printer
{
    /**
     * @type BracketSpace_Notification_Dependencies_HTMLPurifier_CSSDefinition
     */
    protected $def;

    /**
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @return string
     */
    public function render($config)
    {
        $this->def = $config->getCSSDefinition();
        $ret = '';

        $ret .= $this->start('div', array('class' => 'BracketSpace_Notification_Dependencies_HTMLPurifier_Printer'));
        $ret .= $this->start('table');

        $ret .= $this->element('caption', 'Properties ($info)');

        $ret .= $this->start('thead');
        $ret .= $this->start('tr');
        $ret .= $this->element('th', 'Property', array('class' => 'heavy'));
        $ret .= $this->element('th', 'Definition', array('class' => 'heavy', 'style' => 'width:auto;'));
        $ret .= $this->end('tr');
        $ret .= $this->end('thead');

        ksort($this->def->info);
        foreach ($this->def->info as $property => $obj) {
            $name = $this->getClass($obj, 'AttrDef_');
            $ret .= $this->row($property, $name);
        }

        $ret .= $this->end('table');
        $ret .= $this->end('div');

        return $ret;
    }
}

// vim: et sw=4 sts=4
