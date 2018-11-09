<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/8
 * Time: 2:36 PM
 */

namespace epii\cli\i;


use epii\cli\Args;


interface IRun
{
    public function run(Args $args);
}