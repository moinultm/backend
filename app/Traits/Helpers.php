<?php

namespace App\Traits;



trait Helpers
{

     function    ref($num){
        switch ($num) {
            case $num < 10:
                return "000".$num;
                break;
            case $num >= 10 && $num < 100:
                return "00".$num;
                break;
            case $num >+ 10 && $num >= 100 && $num < 1000:
                return "0".$num;
                break;
            default:
                return $num;
        }
    }


    function filterTo($date){
        return $date->endOfDay();
    }


    function filterFrom($date){
        return $date->startOfDay();
    }


}