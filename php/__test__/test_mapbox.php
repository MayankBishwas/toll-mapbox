<?php
//using mapbox API

//Source and Destination Coordinates..
function getPolyline($source_longitude,$source_latitude,$destination_longitude,$destination_latitude){
$key = 'mapbox_api_key';

$url='https://api.mapbox.com/directions/v5/mapbox/driving/'.$source_longitude.','.$source_latitude.';'.$destination_longitude.','.$destination_latitude.'?geometries=polyline&access_token='.$key.'&overview=full';

//connection..
$mapbox = curl_init();

curl_setopt($mapbox, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($mapbox, CURLOPT_SSL_VERIFYPEER, false);

curl_setopt($mapbox, CURLOPT_URL, $url);
curl_setopt($mapbox, CURLOPT_RETURNTRANSFER, true);

//getting response from googleapis..
$response = curl_exec($mapbox);
$err = curl_error($mapbox);

curl_close($mapbox);

if ($err) {
	  echo "cURL Error #:" . $err;
} else {
	  echo "200 : OK\n";
}

//extracting polyline from the JSON response..
$data_mapbox = json_decode($response, true);
//polyline..
$p_mapbox = $data_mapbox['routes']['0']['geometry'];

return $p_mapbox;
}


require_once(__DIR__.'/test_location.php');
require_once(__DIR__.'/get_lat_long.php');
foreach ($locdata as $item) {
$source = getCord($item['from']);
$source_longitude = $source['y'];
$source_latitude = $source['x'];
$destination = getCord($item['to']);
$destination_longitude = $destination['y'];
$destination_latitude = $destination['x'];

$polyline_mapbox = getPolyline($source_longitude,$source_latitude,$destination_longitude,$destination_latitude);


//using tollguru API..
$curl = curl_init();

curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);


$postdata = array(
	"source" => "gmaps",
	"polyline" => $polyline_mapbox
);

//json encoding source and polyline to send as postfields..
$encode_postData = json_encode($postdata);

curl_setopt_array($curl, array(
CURLOPT_URL => "https://dev.tollguru.com/v1/calc/route",
CURLOPT_RETURNTRANSFER => true,
CURLOPT_ENCODING => "",
CURLOPT_MAXREDIRS => 10,
CURLOPT_TIMEOUT => 30,
CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
CURLOPT_CUSTOMREQUEST => "POST",


//sending mapbox polyline to tollguru
CURLOPT_POSTFIELDS => $encode_postData,
CURLOPT_HTTPHEADER => array(
				      "content-type: application/json",
				      "x-api-key: tollguru_api_key"),
));

$response = curl_exec($curl);
$err = curl_error($curl);

curl_close($curl);

if ($err) {
	  echo "cURL Error #:" . $err;
} else {
	  echo "200 : OK\n";
}

//response from tollguru..
$data = json_decode($response, true);

$tag = $data['route']['costs']['tag'];
$cash = $data['route']['costs']['cash'];

$dumpFile = fopen("dump.txt", "a") or die("unable to open file!");
fwrite($dumpFile, "from =>");
fwrite($dumpFile, $item['from'].PHP_EOL);
fwrite($dumpFile, "to =>");
fwrite($dumpFile, $item['to'].PHP_EOL);
fwrite($dumpFile, "polyline =>".PHP_EOL);
fwrite($dumpFile, $polyline_mapbox.PHP_EOL);
fwrite($dumpFile, "tag =>");
fwrite($dumpFile, $tag.PHP_EOL);
fwrite($dumpFile, "cash =>");
fwrite($dumpFile, $cash.PHP_EOL);
fwrite($dumpFile, "*************************************************************************".PHP_EOL);

echo "tag = ";
print_r($data['route']['costs']['tag']);
echo "\ncash = ";
print_r($data['route']['costs']['cash']);
echo "\n";
echo "**************************************************************************\n";
}
?>