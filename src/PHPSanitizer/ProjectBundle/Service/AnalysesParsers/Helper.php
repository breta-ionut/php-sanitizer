<?php

namespace PHPSanitizer\ProjectBundle\Service\AnalysesParsers;

use PHPSanitizer\ProjectBundle\Service\AnalysesParsers\Settings;

/**
 * Class which offers common functionality to the analyses parsers.
 */
class Helper
{    
    /**
     * Generates a color property to be used in CSS from a rgb array.
     * 
     * @param array $rgb
     * @param float $opacity
     * 
     * @return string
     */
    public function generateColorForCss(array $rgb, $opacity = 1)
    {
        list ($red, $blue, $green) = $rgb;

        return "rgba($red, $blue, $green, $opacity)";
    }
    
    /**
     * Computes a random two-digits hexadecimal number for the purpose of generating colors.
     * 
     * @return string
     */
    private function generateRandomHexaNumber()
    {
        $number = dechex(mt_rand(0, 224));
        if (strlen($number) === 1) {
            $number = '0' . $number;
        }
        
        return $number;
    }
    
    /**
     * Generates a random color in hexadecimal format.
     * 
     * @return string
     */
    public function generateRandomColor()
    {
        $red = $this->generateRandomHexaNumber();
        $green = $this->generateRandomHexaNumber();
        $blue = $this->generateRandomHexaNumber();
        
        return "#{$red}{$green}{$blue}";
    }
    
    /**
     * Generates a float random number with the given precision.
     * 
     * @param int $max
     * @param int $precision
     * 
     * @return float
     */
    public function generateRandomNumber($max, $precision = Settings::RANDOM_NUMBER_DEFAULT_PRECISION)
    {
        $quantifier = pow(10, $precision);
        
        return mt_rand(0, $max * $quantifier) / $quantifier;
    }
    
    /**
     * Validates the given url and returns it if is valid. Otherwise, it returns null.
     * 
     * @param string $url
     * 
     * @return string|null
     */
    public function filterUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }
        
        return null;
    }
}
