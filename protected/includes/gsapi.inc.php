<?php

class GSAPI
{
    public static $host = GS_HOST;
    public static $endpoint = GS_ENDPOINT;
    public static $wsKey = GS_WS_KEY;
    public static $secret = GS_SECRET;
    private static $sessionID = '';
    private static $debug = false;
    private static $authenticated = false;

    public static function setSessionID($sessionID)
    {
        self::$sessionID = $sessionID;
    }

    public static function setDebug($debug)
    {
        self::$debug = (bool)$debug;
    }

    public static function createMessageSig($request)
    {
        $sig = hash_hmac('md5', $request, self::$secret);
        return $sig;
    }

    public static function getProtocolForMethod($method)
    {
        switch ($method) {
            case 'startSession':
            case 'authenticateUser':
            case 'authenticate':
            case 'registerUser':
            case 'createTrial':
            case 'getTrialInfo':
                return 'https';
        }

        return 'http';
    }

    public static function callRemote($method, $params = array(), &$requestDetails = null)
    {
        $protocol = self::getProtocolForMethod($method);
        $request = array('method' => '',
                         'header' => array(),
                         'parameters' => array(),
                         );
        if (is_array($params)) {
            $request['parameters'] = $params;
        }
        $request['method'] = $method;
        $request['header']['wsKey'] = self::$wsKey;
        if (isset(self::$sessionID) && !empty(self::$sessionID)) {
            $request['header']['sessionID'] = self::$sessionID;
        }
        $requestJSON = json_encode($request);
        $sig = self::createMessageSig($requestJSON);

        $url = $protocol . '://' . self::$host . '/' . self::$endpoint . '?sig=' . $sig;
        print_r($request);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $requestJSON);
        $result = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $decoded = json_decode($result, true);
        $requestDetails = array('url' => $url, 'http' => $httpCode, 'raw' => $result, 'decoded' => $decoded);
        return $decoded;
    }
    
    public static function doAuthentication($username, $password)
    {
        if (!$authenticated)
        {
            $resp = GSAPI::callRemote('startSession');
            $sessionId = $resp['sessionID'];
            GSAPI::setSessionID($sessionId);
            GSAPI::callRemote('authenticate', array('login' => $username, 'password' => $password));
            $authenticated = true;
        }
    }
}
?>