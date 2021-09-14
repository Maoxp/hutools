<?php

//use Maoxp\Tool\core\util\RandomUtil;
//
//require __DIR__ . "vendor/autoload.php";
//echo RandomUtil::randomInt(), PHP_EOL;

abstract class OverloadObject
{
    public function __call($name, $args)
    {
        $method = $name . "_" . count($args);
        if (!method_exists($this, $method)) {
            throw new Exception("Call to undefined method" . get_class($this) . "::$method");
        }
        return call_user_func_array(array($this, $method), $args);
    }
}

/**
 * Class Multiplier
 *@method int borp() multiply(int $int1, int $int2,int $int3) multiply two integers
 */
class Multiplier extends OverloadObject
{
    function multiply_2($one, $two)
    {
        return $one * $two;
    }

    function multiply_0()
    {
        return 1;
    }

    function multiply_3($one, $two, $three)
    {
        return $one * $two * $three;
    }
}

$multiplier = new Multiplier();
echo $multiplier->multiply().PHP_EOL;
echo $multiplier->multiply(5,6).PHP_EOL;
echo $multiplier->multiply(5,6,7).PHP_EOL;
