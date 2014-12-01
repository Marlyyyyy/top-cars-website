<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 01/12/14
 * Time: 21:34
 */

namespace Marton\TopCarsBundle\Classes;

use Symfony\Component\HttpFoundation\File;

class FileHelper {

    public function guessExtension($file_name){

        $arr = (explode(".", $file_name));
        $ext = end($arr);
        return $ext;
    }

    public function makeUniqueName($key, $file_name){

        $now = getDate();
        $ext = $this->guessExtension($file_name);
        $unique_name = $key.'_'.$now['year'].$now['mon'].$now['mday'].$now['hours'].$now['minutes'].$now['seconds'].'.'.$ext;

        return $unique_name;
    }

} 