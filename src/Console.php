<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/8
 * Time: 2:14 PM
 */

namespace epii\cli;



class Console
{

    public static function show($msg)
    {
        // TODO: Implement show() method.
        echo $msg;
    }

    public  static function exit($msg)
    {
        // TODO: Implement exit() method.
        self::show($msg);
        exit;
    }
}