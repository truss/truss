<?php

/*
 *
 */

namespace Truss;

/**
 * Twig extension to show the time since a date.
 *
 * taken from https://github.com/sgomez/Twig-extensions/blob/15a3950b506adff5b53cc478efeb4cffd292773e/lib/Twig/Extensions/Extension/Date.php
 */
class TwigTimeSince extends \Twig_Extension
{
    protected $en = array(
                'date.year' => array('%year% year', '%year% years'),
                'date.month' => array('%month% month', '%month% months'),
                'date.day' => array('%day% day', '%day% days'),
                'date.hour' => array('%hour% hour', '%hour% hours'),
                'date.minute' => array('%minute% minute', '%minute% minutes'),
                'date.second' => array('%second% second', '%second% seconds'),
                'date.new' => array('less than a minute'),
                'date.and' => array(' and ')
            );
    /**
     * Returns a list of filters.
     *
     * @return array
     */
    public function getFilters() {
        return array(
            'timesince' => new \Twig_Filter_Method($this, 'datediff'),
        );
    }

    /**
     * Name of this extension
     *
     * @return string
     */
    public function getName() {
        return 'Date';
    }

    protected function transChoice($key, $val, $strings, $domain=null, $locale=null) {
        $w = $val > 1 ? 1 : 0;
        return str_replace( $strings[0], $strings[1], $this->en[$key][$w]);
    }

    protected function trans($key, $val, $domain=null, $locale=null) {
        return $this->en[$key][0];
    }
    
    public function datediff($date, $timezone = null, $domain = "TwigExtensionsDate", $locale = null) {
        if (!$date instanceof DateTime) {
            if (ctype_digit((string) $date)) {
                $date = new DateTime('@' . $date);
                $date->setTimezone(new DateTimeZone(date_default_timezone_get()));
            } else {
                $date = new DateTime($date);
            }
        }

        $now = new DateTime("now");

        if (null !== $timezone) {
            if (!$timezone instanceof DateTimeZone) {
                $timezone = new DateTimeZone($timezone);
            }

            $date->setTimezone($timezone);
            $now->setTimezone($timezone);
        }

        // from http://es2.php.net/manual/en/function.ngettext.php
        $interval = $now->diff($date);

        $format = array();
        if ($interval->y !== 0) {
            $format[] = $this->transChoice(
                    'date.year', 
                    $interval->y, 
                    array('%year%' => $interval->y), 
                    $domain, 
                    $locale
            );
        }
        if ($interval->m !== 0) {
            $format[] = $this->transChoice(
                    'date.month', 
                    $interval->m, 
                    array('%month%' => $interval->m), 
                    $domain, 
                    $locale
            );
        }
        if ($interval->d !== 0) {
            $format[] = $this->transChoice(
                    'date.day', 
                    $interval->d, 
                    array('%day%' => $interval->d), 
                    $domain, 
                    $locale
            );
        }
        if ($interval->h !== 0) {
            $format[] = $this->transChoice(
                    'date.hour', 
                    $interval->h, 
                    array('%hour%' => $interval->h), 
                    $domain, 
                    $locale
            );
        }
        if ($interval->i !== 0) {
            $format[] = $this->transChoice(
                    'date.minute', 
                    $interval->i, 
                    array('%minute%' => $interval->i), 
                    $domain, 
                    $locale
            );
        }
        if ($interval->s !== 0) {
            if (!count($format)) {
                return $this->trans(
                        'date.now', 
                        array(), 
                        $domain, 
                        $locale
                );
            } else {
                $format[] = $this->transChoice(
                        'date.second', 
                        $interval->i, 
                        array('%second%' => $interval->i), 
                        $domain, 
                        $locale
                );
            }
        }

        // We use the two biggest parts
        if (count($format) > 1) {
            $format = array_shift($format) .
                    $this->trans(
                            "date.and", 
                            array(), 
                            $domain, 
                            $locale
                    ) .
                    array_shift($format);
        } else {
            $format = array_pop($format);
        }

        return $format;
    }
}
