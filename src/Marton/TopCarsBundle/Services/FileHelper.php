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

    /**
     * Returns the most likely extension of a given file.
     * @param string $fileName
     * @return string
     */
    public function guessExtension($fileName){

        $arr = (explode(".", $fileName));
        $ext = end($arr);
        return $ext;
    }

    /**
     * Returns a uniquely generated name for a file.
     * @param int $key
     * @param string $fileName
     * @return string
     */
    public function makeUniqueName($key, $fileName){

        $now = getDate();
        $ext = $this->guessExtension($fileName);
        $uniqueName = $key.'_'.$now['year'].$now['mon'].$now['mday'].$now['hours'].$now['minutes'].$now['seconds'].'.'.$ext;
        $uniqueName = uniqid() . $uniqueName;

        return $uniqueName;
    }

    /**
     * Removes a file with the provided path.
     * @param string $path
     * @return void
     */
    public function removeFile($path){

        if (file_exists($path)){

            $imageFile = new File($path);

            if (is_writable($imageFile)){

                unlink($imageFile);
            }
        }
    }
}