<?php
use WHMCS\Database\Capsule;

function getZcloudConfig($setting)
{
 $result = Capsule::table("tbladdonmodules")
  ->where("module", "zoneclouddomainsearch")
  ->where("setting", $setting)
  ->value("value");

 return $result;
}
add_hook("IntelligentSearch", 1, function ($vars) {
 /**
  * This is an example of array return for an Intelligent Search.
  * This format is supported in the blend WHMCS Admin Template.
  * Any template based on blend and updated to WHMCS 7.7+ is also supported.
  */

 $query = urlencode($vars["searchTerm"]);
 $zcloud_url = getZcloudConfig("zcloud_url");
 $zcloud_token = getZcloudConfig("zcloud_token");
 $zcloud_exact = getZcloudConfig("zcloud_exact");
 $match = $zcloud_exact ? "exact" : "contains";

 $zcloud_final_url = "{$zcloud_url}/api/find-zone-info/{$query}/{$match}";
 $searchResults = [];
 $headers = ["Authorization: " . $zcloud_token];

 // Initialize cURL
 $ch = curl_init();

 curl_setopt($ch, CURLOPT_URL, $zcloud_final_url);
 curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
 /*
  ** set connect timeout to 2 seconds (we don't want to slow down the search operation in WHMCS in case the controller is down or there is a network problem)
  */
 curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);

 $response = curl_exec($ch);

 // Check for errors
 if (curl_errno($ch)) {
  $error = "Request Error: " . curl_error($ch);
 } else {
  $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($httpCode !== 200) {
   $error = "API returned HTTP Code " . $httpCode;
  } else {
   $apiData = json_decode($response);
   if (json_last_error() !== JSON_ERROR_NONE) {
    $error = "Invalid JSON response";
   } else {
    $results = $apiData;
   }
  }
 }

 curl_close($ch);
 if (empty($error)) {
  foreach ($results->zones as $zone) {
   $searchResults[] = [
    "title" => $zone->zone,
    "href" => "#",
    "subTitle" => "User: {$zone->owner}, Server: {$zone->server_name}",
    "icon" => "fal fa-globe",
   ];
  }
 }

 return $searchResults;
});
