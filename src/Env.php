<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/8
 * Time: 3:28 PM
 */

namespace epii\cli;


class Env
{
    private static $args = [];

    public static function setArgs($args)
    {
        self::$args = $args;
    }

    public static function get($k = null)
    {
        if ($k == null) {
            return self::$args;
        } else {
            if (isset(self::$args[$k])) {
                return self::$args[$k];
            }
        }
        return "";
    }
}