<?php
/**
 * Created by PhpStorm.
 * User: Marci
 * Date: 05/12/14
 * Time: 13:14
 */

namespace Marton\TopCarsBundle\Services;


use Symfony\Component\HttpFoundation\File\File;

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

    public function removeFile($path){

        if (file_exists($path)){

            $image_file = new File($path);

            if (is_writable($image_file)){

                unlink($image_file);
            }
        }
    }
} 