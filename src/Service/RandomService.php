<?php

namespace App\Service;

/**
 * gets a random string
 *
 * @author DMoreau
 */
class RandomService {

    private $allowedChars = [
        'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q','r','s','t','u','v','w','x','y','z',
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
        '0', '1', '2', '3', '4', '5', '6', '7', '8', '9'
    ];

    /**
     * Renvoie une string aléatoire (a-zA-Z0-9) de longueur $length
     *
     * @param integer $length : longueur de la chaine souhaitée
     * @return string
     */
    public function getRandomString(int $length){

        $string = '';
        $counter = 0;
        while ($counter < $length){
            $string = $string . $this->allowedChars[array_rand($this->allowedChars)];
            $counter++;
        }
        return $string;
    }
}