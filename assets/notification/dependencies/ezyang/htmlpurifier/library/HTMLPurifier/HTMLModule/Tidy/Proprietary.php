<?php
/**
 * @license LGPL-2.1-or-later
 *
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */

class BracketSpace_Notification_Dependencies_HTMLPurifier_HTMLModule_Tidy_Proprietary extends BracketSpace_Notification_Dependencies_HTMLPurifier_HTMLModule_Tidy
{

    /**
     * @type string
     */
    public $name = 'Tidy_Proprietary';

    /**
     * @type string
     */
    public $defaultLevel = 'light';

    /**
     * @return array
     */
    public function makeFixes()
    {
        $r = array();
        $r['table@background'] = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['td@background']    = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['th@background']    = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['tr@background']    = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['thead@background'] = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['tfoot@background'] = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['tbody@background'] = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Background();
        $r['table@height']     = new BracketSpace_Notification_Dependencies_HTMLPurifier_AttrTransform_Length('height');
        return $r;
    }
}

// vim: et sw=4 sts=4
