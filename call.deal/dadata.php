<?php require_once ($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

class Dadata {
    private $base_url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs";
    private $token;
    private $handle;

    function __construct($token) {
        $this->token = $token;
    }

    public function init() {
        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $this->token
        ));
        curl_setopt($this->handle, CURLOPT_POST, 1);
    }

    /**
     * See for details:
     *   - https://dadata.ru/api/find-address/
     *   - https://dadata.ru/api/find-bank/
     *   - https://dadata.ru/api/find-fias/
     *   - https://dadata.ru/api/find-party/
     */
    public function findById($type, $fields) {
        $url = $this->base_url . "/findById/$type";
        return $this->executeRequest($url, $fields);
    }

    /**
     * See https://dadata.ru/api/geolocate/ for details.
     */
    public function geolocate($lat, $lon, $count = 10, $radius_meters = 100) {
        $url = $this->base_url . "/geolocate/address";
        $fields = array(
            "lat" => $lat,
            "lon" => $lon,
            "count" => $count,
            "radius_meters" => $radius_meters
        );
        return $this->executeRequest($url, $fields);
    }

    /**
     * See https://dadata.ru/api/iplocate/ for details.
     */
    public function iplocate($ip) {
        $url = $this->base_url . "/iplocate/address?ip=" . $ip;
        return $this->executeRequest($url, $fields = null);
    }

    /**
     * See https://dadata.ru/api/suggest/ for details.
     */
    public function suggest($type, $fields) {
        $url = $this->base_url . "/suggest/$type";
        return $this->executeRequest($url, $fields);
    }

    public function close() {
        curl_close($this->handle);
    }

    private function executeRequest($url, $fields) {
        curl_setopt($this->handle, CURLOPT_URL, $url);
        if ($fields != null) {
            curl_setopt($this->handle, CURLOPT_POST, 1);
            curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));
        } else {
            curl_setopt($this->handle, CURLOPT_POST, 0);
        }
        $result = $this->exec();
        $result = json_decode($result, true);
        return $result;
    }

    private function exec() {
        $result = curl_exec($this->handle);
        $info = curl_getinfo($this->handle);
        if ($info['http_code'] == 429) {
            throw new TooManyRequests();
        } elseif ($info['http_code'] != 200) {
            throw new Exception('Request failed with http code ' . $info['http_code'] . ': ' . $result);
        }
        return $result;
    }
}

if(isset($_REQUEST['dadata']) && isset($_REQUEST['getCountry']) && $_REQUEST['getCountry'] === 'Y'){
    echo json_encode(getCountry());
} elseif(isset($_REQUEST['dadata']) && isset($_REQUEST['getRegion']) && $_REQUEST['getRegion'] === 'Y'){
    echo json_encode(getRegion());
} elseif(isset($_REQUEST['dadata']) && isset($_REQUEST['getCity']) && $_REQUEST['getCity'] === 'Y'){
    echo json_encode(getCity());
} elseif(isset($_REQUEST['dadata']) && isset($_REQUEST['getHouse']) && $_REQUEST['getHouse'] === 'Y'){
    echo json_encode(getHouse());
}

function getCountry(){
    $dadata = new Dadata("aa0bf5e24721e59e4971b4c79ba8628c982397e8");
    $dadata->init();
    $query = htmlspecialchars(trim($_POST['query']));
    $count = 3;
    $fields = array("query"=>$query,"count"=>$count);
    $result = $dadata->suggest("country", $fields);
    $countries = array();
    foreach($result['suggestions'] as $country){
        $countries[$country['data']['code']] = $country['value'];
    }

    $countries = array(
        643 => "Россия",
        398 => "Казахстан",
        804 => "Украина",
        112 => "Беларусь"
    );

    return $countries;
}

function getRegion(){
    $dadata = new Dadata("aa0bf5e24721e59e4971b4c79ba8628c982397e8");
    $dadata->init();
    $query = htmlspecialchars(trim($_POST['query']));
    $count = 3;
    $restriction = array("country" => htmlspecialchars(trim($_POST['restriction'])));
    $fields = array("query"=>$query,"count"=>$count,"locations"=>$restriction,"bounds"=>"region-city");

    $result = $dadata->suggest("address",$fields);
    foreach($result['suggestions'] as $region){
        if($region['data']['city_type_full'] !== 'город'){
            $regions[$region['data']['region']] = $region['value'];
        }
    }


    return $regions;
}

function getCity(){
    $cities = array();
    $dadata = new Dadata("aa0bf5e24721e59e4971b4c79ba8628c982397e8");
    $dadata->init();
    $query = htmlspecialchars(trim($_POST['query']));
    $count = 10;
    $restriction = array("region" => htmlspecialchars(trim($_POST['restriction'])));
    $fields = array("query"=>$query,"count"=>$count,'locations'=>$restriction,"from_bound"=>array("value"=>"city"),"to_bound"=>array("value"=>"settlement"));

    $result = $dadata->suggest("address",$fields);

    foreach($result['suggestions'] as $city){
        $cities[$city['value']] = array($city['data']['city'],$city['data']['settlement'],$city['value']);
    }
    return $cities;
}

function getStreet(){
    $streets = array();
    $dadata = new Dadata("aa0bf5e24721e59e4971b4c79ba8628c982397e8");
    $dadata->init();
    $query = htmlspecialchars(trim($_POST['query']));


}

function getHouse(){
    $houses = array();
    $dadata = new Dadata(\Bitrix\Main\Config\Option::get('pusk.ext', 'dadata_api_key'));
    $dadata->init();
    $query = htmlspecialchars(trim($_POST['query']));
    $count = 100;

    $restriction = $_POST['restriction'];

    if(strlen(htmlspecialchars(trim($_POST['restriction']['dataCity']))) > 0){
        $restriction = array("city" => htmlspecialchars(trim($_POST['restriction']['dataCity'])));
    }

    if(strlen(htmlspecialchars(trim($_POST['restriction']['dataSettlement']))) > 0){
        $restriction = array_merge(array("settlement" => htmlspecialchars(trim($_POST['restriction']['dataSettlement']))),$restriction);
    }

    $fields = array("query" => $query, "count" => $count,'locations'=>$restriction);
    $result = $dadata->suggest("address",$fields);

    foreach($result['suggestions'] as $house){
        if($house['data']['street_with_type'] && strlen($house['data']['street_with_type'])>0){
            if($house['data']['postal_code'] != ""){
                $houses[$house['value']] = array(
                    "value" => $house['value'] ? $house['value'] : "",
                    "postal_code" => $house['data']['postal_code'],
                    "country" => $house['data']['country'] ? $house['data']['country'] : "",
                    "region_with_type" => $house['data']['country'] ? $house['data']['country'] : "",
                    "street_with_type" => $house['data']['street_with_type'] ? $house['data']['street_with_type'] : "",
                    "street" => $house['data']['street'] ? $house['data']['street'] : "",
                    "geo_lat" => $house['data']['geo_lat'] ? $house['data']['geo_lat'] : "",
                    "geo_lon" => $house['data']['geo_lon'] ? $house['data']['geo_lon'] : "",
                    "house_type" => $house['data']['house_type'] ? $house['data']['house_type'] : "",
                    "house_type_full" => $house['data']['house_type_full'] ? $house['data']['house_type_full'] : "",
                    "house" => $house['data']['house'] ? $house['data']['house'] : ""
                );
            }
        }
    }

    return $houses;
}