<?php
/**
 * Created by PhpStorm.
 * User: PHPPer
 * Date: 2018/6/24
 * Time: 21:56
 */

include "./random.php";
$gen = new random();
$username = $gen->get_username_number();
print_r($username);