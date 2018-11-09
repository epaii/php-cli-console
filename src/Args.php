<?php
/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/9
 * Time: 9:41 AM
 */

namespace epii\cli;


use InvalidArgumentException;

class Args implements \ArrayAccess
{

    // store options
    private static $optsArr = [];
    // store args
    private static $argsArr = [];
    // 是否解析过
    private static $isParse = false;

    private static $keysForArgValues = [];
    private static $configs = [];

    public function __construct()
    {
        if (!self::$isParse) {
            self::parseArgs();
        }
    }

    public static function setConfig($config)
    {
        self::$configs = $config;
    }

    public static function setKeysForArgValues($keys)
    {
        if (!self::$keysForArgValues) {

            self::$keysForArgValues = $keys;
            $args_values = self::$argsArr;
            self::$argsArr = [];
            for ($_i = 0; $_i < count(self::$keysForArgValues); $_i++) {
                self::$argsArr[self::$keysForArgValues[$_i]] = isset($args_values[$_i]) ? $args_values[$_i] : null;
            }

        }
    }

    public function getVal($key)
    {
        return $this->offsetGet($key);
    }

    public function getPhpFile()
    {
        global $argv;
        return $argv[0];
    }

    /**
     * 获取选项值
     * @param string|NULL $opt
     * @return array|string|NULL
     */
    public function getOptVal($opt = NULL)
    {
        if (is_null($opt)) {
            return self::$optsArr;
        } else if (isset(self::$optsArr[$opt])) {
            return self::$optsArr[$opt];
        }
        return null;
    }

    public function getConfigVal($key = NULL)
    {
        if (is_null($key)) {
            return self::$configs;
        } else if (isset(self::$configs[$key])) {
            return self::$configs[$key];
        }
        return null;
    }

    /**
     * 获取命令行参数值
     * @param integer|NULL $index
     * @return array|string|NULL
     */
    public function getArgVal($index = NULL)
    {
        if (is_null($index)) {
            return self::$argsArr;
        } else if (isset(self::$argsArr[$index])) {
            return self::$argsArr[$index];
        }
        return null;
    }

    /**
     * 注册选项对应的回调处理函数, $callback 应该有一个参数, 用于接收选项值
     * @param string $opt
     * @param callable $callback 回调函数
     * @throws InvalidArgumentException
     */
    public function option($opt, callable $callback)
    {
        // check
        if (!is_callable($callback)) {
            throw new InvalidArgumentException(sprintf('Not a valid callback <%s>.', $callback));
        }
        if (isset(self::$optsArr[$opt])) {
            call_user_func($callback, self::$optsArr[$opt]);
        } else {
            throw new InvalidArgumentException(sprintf('Unknown option <%s>.', $opt));
        }
    }

    /**
     * 是否是 -s 形式的短选项
     * @param string $opt
     * @return string|boolean 返回短选项名
     */
    private static function isShortOptions($opt)
    {
        if (preg_match('/^\-([a-zA-Z0-9])$/', $opt, $matchs)) {
            return $matchs[1];
        }
        return false;
    }

    /**
     * 是否是 -svalue 形式的短选项
     * @param string $opt
     * @return array|boolean 返回短选项名以及选项值
     */
    private static function isShortOptionsWithValue($opt)
    {
        if (preg_match('/^\-([a-zA-Z0-9])(\S+)$/', $opt, $matchs)) {
            return [$matchs[1], $matchs[2]];
        }
        return false;
    }

    /**
     * 是否是 --longopts 形式的长选项
     * @param string $opt
     * @return string|boolean 返回长选项名
     */
    private static function isLongOptions($opt)
    {
        if (preg_match('/^\-\-([a-zA-Z0-9\-_]{2,})$/', $opt, $matchs)) {
            return $matchs[1];
        }
        return false;
    }

    /**
     * 是否是 --longopts=value 形式的长选项
     * @param string $opt
     * @return array|boolean 返回长选项名及选项值
     */
    private static function isLongOptionsWithValue($opt)
    {
        if (preg_match('/^\-\-([a-zA-Z0-9\-_]{2,})(?:\=(.*?))$/', $opt, $matchs)) {
            return [$matchs[1], $matchs[2]];
        }
        return false;
    }

    /**
     * 是否是命令行参数
     * @param string $value
     * @return boolean
     */
    private static function isArg($value)
    {
        return !preg_match('/^\-/', $value);
    }

    /**
     * 解析命令行参数
     * @return array ['opts'=>[], 'args'=>[]]
     */
    public final static function parseArgs()
    {
        global $argv;
        if (!self::$isParse) {
            // index start from one
            $index = 1;
            $length = count($argv);
            $args_values = [];
            while ($index < $length) {
                // current value
                $curVal = $argv[$index];
                // check, short or long options
                if (($key = self::isShortOptions($curVal)) || ($key = self::isLongOptions($curVal))) {
                    // go ahead
                    $index++;
                    if (isset($argv[$index]) && self::isArg($argv[$index])) {
                        self::$optsArr[$key] = $argv[$index];
                    } else {
                        self::$optsArr[$key] = true;
                        // back away
                        $index--;
                    }
                } // check, short or long options with value
                else if (($key = self::isShortOptionsWithValue($curVal))
                    || ($key = self::isLongOptionsWithValue($curVal))
                ) {
                    self::$optsArr[$key[0]] = $key[1];
                } // args
                else if (self::isArg($curVal)) {
                    $args_values[] = $curVal;
                }
                // incr index
                $index++;
            }


            self::$argsArr = $args_values;


            self::$isParse = true; // change status
        }
        return ['opts' => self::$optsArr, 'args' => self::$argsArr];
    }

    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        // TODO: Implement offsetExists() method.
        return isset(self::$argsArr[$offset]) || isset(self::$optsArr[$offset]) || isset(self::$configs[$offset]);
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        // TODO: Implement offsetGet() method.

        return $this->getArgVal($offset) ?: ($this->getOptVal($offset) ?: $this->getConfigVal($offset));
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        // TODO: Implement offsetSet() method.
        self::$argsArr[$offset] = $value;
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        // TODO: Implement offsetUnset() method.
        unset(self::$argsArr[$offset]);
        unset(self::$optsArr[$offset]);
        unset(self::$configs[$offset]);

    }


}