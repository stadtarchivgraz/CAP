<?php

/**
 * Validates an integer representation of pixels according to the HTML spec.
 *
 * @license LGPL-2.1-or-later
 * Modified by bracketspace on 02-October-2024 using {@see https://github.com/BrianHenryIE/strauss}.
 */
class BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef_HTML_Pixels extends BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef
{

    /**
     * @type int
     */
    protected $max;

    /**
     * @param int $max
     */
    public function __construct($max = null)
    {
        $this->max = $max;
    }

    /**
     * @param string $string
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Config $config
     * @param BracketSpace_Notification_Dependencies_HTMLPurifier_Context $context
     * @return bool|string
     */
    public function validate($string, $config, $context)
    {
        $string = trim($string);
        if ($string === '0') {
            return $string;
        }
        if ($string === '') {
            return false;
        }
        $length = strlen($string);
        if (substr($string, $length - 2) == 'px') {
            $string = substr($string, 0, $length - 2);
        }
        if (!is_numeric($string)) {
            return false;
        }
        $int = (int)$string;

        if ($int < 0) {
            return '0';
        }

        // upper-bound value, extremely high values can
        // crash operating systems, see <http://ha.ckers.org/imagecrash.html>
        // WARNING, above link WILL crash you if you're using Windows

        if ($this->max !== null && $int > $this->max) {
            return (string)$this->max;
        }
        return (string)$int;
    }

    /**
     * @param string $string
     * @return BracketSpace_Notification_Dependencies_HTMLPurifier_AttrDef
     */
    public function make($string)
    {
        if ($string === '') {
            $max = null;
        } else {
            $max = (int)$string;
        }
        $class = get_class($this);
        return new $class($max);
    }
}

// vim: et sw=4 sts=4
