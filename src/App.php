<?php
namespace epii\cli;


use epii\cli\i\IArgsKeys;
use epii\cli\i\IRun;


/**
 * Created by PhpStorm.
 * User: mrren
 * Date: 2018/11/8
 * Time: 2:32 PM
 */
class App
{

    private $args = null;

    private $init_fun = [];


    private $base_namespace = "app";

    public function setBaseNameSpace($base_name)
    {
        $this->base_namespace = $base_name;
        return $this;
    }

    public static function getAppRoot()
    {
        return pathinfo($_SERVER["PHP_SELF"], PATHINFO_DIRNAME);
    }

    public function __construct($configOrFilePath = null)
    {

        // var_dump($_SERVER);
        $root_path = App::getAppRoot();

        if (file_exists($config_file = $root_path . "/config.json")) {
            $config = json_decode(file_get_contents($config_file), true);
        } else if (file_exists($config_file = $root_path . "/../config.json")) {
            $config = json_decode(file_get_contents($config_file), true);
        } else if (is_array($configOrFilePath)) {
            $config = $configOrFilePath;
        } else if (file_exists($config_file = $configOrFilePath)) {
            $config = json_decode(file_get_contents($configOrFilePath), true);
        } else
            $config = [];


        Args::setConfig($config);
        $args = Args::parseArgs();
        Env::setArgs($args['args']);
        $this->args = new Args();

    }

    private function init_one($irun)
    {
        $this->init_fun[] = $irun;
    }

    public function init(...$Iruns)
    {
        if (count($Iruns) > 0) {
            foreach ($Iruns as $irun) {
                if (!is_array($irun)) {
                    $this->init_one($irun);
                } else {
                    array_map(function ($c) {
                        $this->init_one($c);
                    }, $irun);
                }
            }
        }
        return $this;
    }


    public function run($app = null)
    {


        if ($app === null) {

            $options = getopt("a:", ["app:"], $int);
            if (isset($options["a"])) {
                $app = $options["a"];
            } else if (isset($options["app"])) {
                $app = $options["app"];
            } else {
                $app = "index";

            }
            if ($app) {
                $config = $this->args->getConfigVal("app");

                if (isset($config[$app])) {
                    $app = $config[$app];
                } else {
                    $app = str_replace(".", "\\", $app);
                }
            }


        }

        $m = "index";

        if (is_string($app)) {
            if (stripos($app, "@") > 0) {

                list($app, $m) = explode("@", $app);
            }
        }


        if (is_string($app) && (class_exists($app) || class_exists($app = $this->base_namespace . "\\" . $app))) {
            $run = new $app();
            $this->beforRun($run);
            if (method_exists($run, "init")) {
                $run->init($this->args);
            }
            if (method_exists($run, $m)) {
                $run->$m($this->args);
            } else {
                if ($run instanceof IRun) {
                    return $run->run($this->args);
                } elseif (method_exists($run, "__call")) {
                    $run->$m();
                }
            }
        } else {
            $this->beforRun();
            return $this->init_one_run($app);
        }

        return null;


    }

    private function beforRun($app = null)
    {

        if ($app instanceof IArgsKeys) {
            Args::setKeysForArgValues($app->keysForArgValues());

        }

        array_map(function ($irun) {

            $this->init_one_run($irun);
        }, $this->init_fun);

    }

    private function init_one_run($irun)
    {
        if (class_exists($irun)) {
            $tmp = new $irun();
            if ($tmp instanceof IRun) {
                return $tmp->run($this->args);
            }
        } else if (is_callable($irun)) {
            return $irun($this->args);
        }
        return null;
    }
}