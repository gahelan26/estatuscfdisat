<?php

namespace Gahelan\Consulta;

use DOMDocument;
use Exception;
use stdClass;

class Consulta {

    const URL_PRODUCCION = 'https://consultaqr.facturaelectronica.sat.gob.mx/ConsultaCFDIService.svc';
    const URL_PRUEBAS = 'https://pruebacfdiconsultaqr.cloudapp.net/ConsultaCFDIService.svc';

    /**
     * @param $uuid
     * @param $rfc_emisor
     * @param $rfc_receptor
     * @param $total
     * @param null $debug
     * @return Resultado|null
     * @throws Exception
     */
    protected static function call($url, $uuid, $rfc_emisor, $rfc_receptor, $total, &$debug = null) {

        $rfc_emisor_encoded = htmlspecialchars($rfc_emisor, ENT_QUOTES);
        $rfc_receptor_encoded = htmlspecialchars($rfc_receptor, ENT_QUOTES);
        $total_encoded = $total;
        $expresion_impresa = "<![CDATA[?id={$uuid}&re={$rfc_emisor_encoded}&rr={$rfc_receptor_encoded}&tt={$total_encoded}]]>";

        $dom = new DOMDocument;
        $dom->loadXML('<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/" xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/"><s:Body xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><Consulta xmlns="http://tempuri.org/"><expresionImpresa>' . $expresion_impresa . '</expresionImpresa></Consulta></s:Body></s:Envelope>');
        $soap = $dom->saveXML();

        $debug = new stdClass;
        $debug->request = $soap;

        $curl = curl_init();
        curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            'Content-Type: text/xml;charset=utf-8',
            'SOAPAction: "http://tempuri.org/IConsultaCFDIService/Consulta"'
        ));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $soap);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);
        $response = curl_exec($curl);

        $debug->response = $response;

        if (!$response) {
            throw new Exception('No se recibió respuesta del servicio de consulta de estado del SAT');
        }

        $dom = new DOMDocument;
        if (!@$dom->loadXML($response)) {
            return null;
        }
        if ($dom->getElementsByTagName('Fault')->length > 0) {
            return null;
        }

        $resultado = new Resultado;
        $resultado->CodigoEstatus = $dom->getElementsByTagName('CodigoEstatus')->item(0)->nodeValue;
        $resultado->EsCancelable = $dom->getElementsByTagName('EsCancelable')->item(0)->nodeValue;
        $resultado->Estado = $dom->getElementsByTagName('Estado')->item(0)->nodeValue;
        $resultado->EstatusCancelacion = $dom->getElementsByTagName('EstatusCancelacion')->item(0)->nodeValue;
        $resultado->ValidacionEFOS = $dom->getElementsByTagName('ValidacionEFOS')->item(0)->nodeValue;

        return $resultado;
    }

    /**
     * @param $uuid
     * @param $rfc_emisor
     * @param $rfc_receptor
     * @param $total
     * @param null $debug
     * @return Resultado|null
     * @throws Exception
     */
    public static function ConsultaCfdi($uuid, $rfc_emisor, $rfc_receptor, $total, &$debug = null) {
        return static::call(static::URL_PRODUCCION, $uuid, $rfc_emisor, $rfc_receptor, $total, $debug);
    }

    /**
     * @param $uuid
     * @param $rfc_emisor
     * @param $rfc_receptor
     * @param $total
     * @param null $debug
     * @return Resultado|null
     * @throws Exception
     */
    public static function ConsultaCfdiPruebas($uuid, $rfc_emisor, $rfc_receptor, $total, &$debug = null) {
        return static::call(static::URL_PRUEBAS, $uuid, $rfc_emisor, $rfc_receptor, $total, $debug);
    }
}