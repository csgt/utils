<?php
namespace Csgt\Utils;

use Exception;
use SoapClient;
use Carbon\Carbon;
use NumberFormatter;

class Utils
{
    private static $monedas = [
        'Q'   => ['country' => 'Guatemala', 'currency' => 'Q', 'singular' => 'QUETZAL', 'plural' => 'QUETZALES', 'symbol' => 'Q'],
        'GTQ' => ['country' => 'Guatemala', 'currency' => 'GTQ', 'singular' => 'QUETZAL', 'plural' => 'QUETZALES', 'symbol' => 'GTQ'],
        'US$' => ['country' => 'Estados Unidos', 'currency' => 'USD', 'singular' => 'DÓLAR', 'plural' => 'DÓLARES', 'symbol' => 'US$'],
    ];

    public static function numberSpellout($aNumber, $aCurrency = null, $aDecimals = 2, $aDecimalSeparator = '.', $aDecimalSeparatorWord = 'con')
    {
        $number = explode($aDecimalSeparator, $aNumber);

        $f        = new NumberFormatter(config('app.locale'), NumberFormatter::SPELLOUT);
        $output   = $f->format($number[0]);
        $decimals = "";
        //CHAPUSssssssss
        if ($output == 'uno') {
            $output = 'un';
        }

        if ($aCurrency != null) {
            $output .= " " . self::$monedas[$aCurrency][($number[0] == 1 ? 'singular' : 'plural')];
        }

        if (count($number) > 1 && $aDecimals > 0) {

            $decimalArray = str_split($number[1]);

            foreach ($decimalArray as $decimal) {
                if ($decimal == 0) {
                    $decimals .= $f->format($decimal) . " ";
                } else {
                    break;
                }
            }

            $decimals .= $f->format(str_pad($number[1], $aDecimals, "0"));
            $output .= " $aDecimalSeparatorWord $decimals";
        }

        return $output;

    }

    public static function fechaHumanoAMysql($aFecha, $aSeparador = '/')
    {
        $fh = explode(' ', $aFecha);
        if (sizeof($fh) == 2) {
            $formato    = 'd' . $aSeparador . 'm' . $aSeparador . 'Y H:i';
            $formatoOut = 'Y-m-d H:i';
            $aFecha     = substr($aFecha, 0, 16);
        } else {
            $formato    = 'd' . $aSeparador . 'm' . $aSeparador . 'Y';
            $formatoOut = 'Y-m-d';
        }

        try {
            $fecha = Carbon::createFromFormat($formato, $aFecha);

            return $fecha->format($formatoOut);
        } catch (Exception $e) {
            return '0000-00-00 00:00';
        }
    }

    public static function fechaMysqlAHumano($aFecha, $aSeparador = '/')
    {
        $fh = explode(' ', $aFecha);
        if (sizeof($fh) == 2) {
            $formatoOut = 'd' . $aSeparador . 'm' . $aSeparador . 'Y H:i';
            $formato    = 'Y-m-d H:i';
            $aFecha     = substr($aFecha, 0, 16);
        } else {
            $formatoOut = 'd' . $aSeparador . 'm' . $aSeparador . 'Y';
            $formato    = 'Y-m-d';
        }

        try {
            $fecha = Carbon::createFromFormat($formato, $aFecha);

            return $fecha->format($formatoOut);
        } catch (Exception $e) {
            return '00-00-0000 00:00';
        }
    }

    public static function tipoCambio()
    {
        try {
            $soapClient = new SoapClient("http://www.banguat.gob.gt/variables/ws/TipoCambio.asmx?wsdl", ["trace" => 1]);
            $info       = $soapClient->__call("TipoCambioDia", []);

            return $info->TipoCambioDiaResult->CambioDolar->VarDolar->referencia;
        } catch (Exception $e) {
            return 0;
        }
    }
}
