<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Description of FileUploader
 *
 * @author DMoreau
 */
class FileUploader {
   
    private $targetDirectory;
    
    public function __construct($targetDirectory) {
        $this->targetDirectory = $targetDirectory;
    }
    
    /**
     * 
     * @param UploadedFile $file
     * @param string $filename
     * @param bool $createThumb
     * @return string filename of file
     */
    public function upload(UploadedFile $file, $filename, $createThumb  = false){
        // $filename = $filename.'.'.$file->guessExtension();
        try {
            
            if (!file_exists($this->getTargetDirectory())) {
                mkdir($this->getTargetDirectory(), 0644, true);
            }
            
            $targetFile = $file->move($this->getTargetDirectory(), $filename);
            
            if($createThumb){
                $this->createThumb($targetFile);
            }
        } catch (FileException $e) {
            return 'error';
        }

        return $filename;
    }
    
    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }
    
    public function createThumb($file){

        $filename = $file->getFileName();
        $thumbPath = $this->getTargetDirectory(). DIRECTORY_SEPARATOR . 'thumb_'.$filename;

        $width = 200;
        $height = 200;
        list($width_orig, $height_orig) = getimagesize($file);

        //ne pas agrandir l'image
        if($width_orig <= $width && $height_orig <= $width){
            copy($this->getTargetDirectory(). DIRECTORY_SEPARATOR . $filename , $thumbPath);
            return;
        }
        
        $ratio_orig = $width_orig/$height_orig;
        
        if ($width/$height > $ratio_orig) {
            $width = $height*$ratio_orig;
        } else {
            $height = $width/$ratio_orig;
        }
        
        switch($file->guessExtension()){
            case 'png':
                $image = imagecreatefrompng($file);
                break;
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file);
                break;
            case 'gif':
                $image = imagecreatefromgif($file);
                break;

        }
        $image_p = imagecreatetruecolor($width, $height);

        $background = imagecolorallocate($image_p , 0, 0, 0);
        // removing the black from the placeholder
        imagecolortransparent($image_p, $background);

        // turning off alpha blending (to ensure alpha channel information
        // is preserved, rather than removed (blending with the rest of the
        // image in the form of black))
        imagealphablending($image_p, false);

        // turning on alpha channel information saving (to ensure the full range
        // of transparency is preserved)
        imagesavealpha($image_p, true);
        imagecopyresampled($image_p, $image, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);

        switch($file->guessExtension()){
            case 'png':
                imagepng($image_p, $thumbPath);
                break;
            case 'jpg':
            case 'jpeg':
                imagejpeg($image_p, $thumbPath);
                break;
            case 'gif':
                imagegif($image_p, $thumbPath);
                break;
        }
    }
}
