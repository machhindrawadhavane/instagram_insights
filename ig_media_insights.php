<?php
echo "<pre>";
ini_set('max_execution_time',0);
date_default_timezone_set('Asia/Calcutta');
$start = microtime(true);
require_once __DIR__ . '/vendor/autoload.php';
require "database_connection.php";
require "db_config.php";
$link = new Database(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE);
$conn = $link->connect();
$fb = new \Facebook\Facebook([
    'app_id' => '1084558868569174',
    'app_secret' => '3102f6ed34215835cb029b5fce29325c',
    'default_graph_version' => 'v6.0',
    //'default_access_token' => '{access-token}', // optional
]);
$isSaveUpdateMediaPostInsights = false;
$isSaveUpdateMediaIds = false;
$app_token = 'EAAPaZAooZAXFYBAMgjr4WKiywi51l985lH3fb9WZCKdZCo4y8ZAZB4h3bkRPyye3X99va98BXP3NpR7MLkbZCwzMZAZBacWrgCL1lQBBY4GEHz2vPJuZAS3DWTWG68sn3e2XydlAPVTXzQNktpjrcd7bJv5eitwTnBkIvN81RpmUIOigZDZD';
$pageId = '1954072204870360';
$pageName = "NEWJ";
$instagramBusinessAccountId = '17841407454307610';
function checkTokenValidity($token)
{
    global $fb;
    $oauth = $fb->getOAuth2Client();
    $meta = $oauth->debugToken($token);
    return $meta->getIsValid();
}

checkTokenValidity($app_token);
if($isSaveUpdateMediaIds == true){
	saveUpdateInstagramMediaIdFromBusinessAccountId($app_token);
}

if($isSaveUpdateMediaPostInsights == true){
	$query = "select * from ig_media_ids where page_name = '".$pageName."' ";
	$result = mysqli_query($conn, $query);
	while ($mediaData = mysqli_fetch_assoc($result)) {
		postInstagramMediaInsightsDataLifeTimeByMediaId($mediaData,$app_token);
	}
}

function saveUpdateInstagramMediaIdFromBusinessAccountId($token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	$url = $instagramBusinessAccountId.'/media';
	try {
			$resp = $fb->get($url,$token);
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		$pagesEdge = $resp->getGraphEdge();
		do {
			foreach($pagesEdge as $mediaData){
				//print_r($mediaData->asArray());die;
				$query = "select * from ig_media_ids where ig_media_id = '".$mediaData['id']."' ";
				$result = mysqli_query($conn, $query);
				$rowcount=mysqli_num_rows($result);
				if($rowcount > 0){
				   $sql = "UPDATE ig_media_ids SET ig_media_id='".$mediaData['id']."' WHERE ig_media_id = '".$mediaData['id']."' ";
				}else{
				   $sql = "INSERT INTO `ig_media_ids` (page_id,ig_business_account_id,page_name,ig_media_id) VALUES ('".$pageId."','".$instagramBusinessAccountId."','".$pageName."','".$mediaData['id']."') ";
				}
				$conn->query($sql);
			}
		} while ($pagesEdge = $fb->next($pagesEdge));
}

function postInstagramMediaInsightsDataLifeTimeByMediaId($mediaData = array(),$token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	$url = $mediaData['ig_media_id'].'/insights?metric=engagement,impressions,reach,saved,video_views&period=lifetime';
	try {
			$resp = $fb->get($url,$token);
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		$pagesEdge = $resp->getGraphEdge();
		do {
			//print_r($pagesEdge->asArray());die;
			$vls = "'".$mediaData['page_id']."','".$mediaData['ig_business_account_id']."','".$mediaData['page_name']."','".$mediaData['ig_media_id']."','lifetime'";
			$insertQueryColumns = '`page_id`,`ig_business_account_id`,`page_name`,`ig_media_id`,`period`';
			$updateQueryCoulumnValues = "page_id='".$mediaData['page_id']."',ig_business_account_id='".$mediaData['ig_business_account_id']."',page_name='".$mediaData['page_name']."',ig_media_id='".$mediaData['ig_media_id']."',period='lifetime' ";
			foreach($pagesEdge as $userInsights){
				$insertQueryColumns.= ',`'.$userInsights['name'].'`';
				$value = isset($userInsights['values'][0]['value']) ? $userInsights['values'][0]['value'] : 0;
				$updateQueryCoulumnValues.= ",".$userInsights['name']."='".$value."' ";
				$vls .= ",'".$value."' ";
			}
			$query = "select * from ig_post_media_insights where ig_media_id = '".$mediaData['ig_media_id']."' ";
			$result = mysqli_query($conn, $query);
			$rowcount=mysqli_num_rows($result);
			if($rowcount > 0){
				$sql = "UPDATE ig_post_media_insights SET $updateQueryCoulumnValues WHERE ig_media_id = '".$mediaData['ig_media_id']."' ";
			}else{
				$sql = "INSERT INTO ig_post_media_insights (".$insertQueryColumns.") VALUES (".$vls.") ";
			}
			echo $sql.'</br>';
			$conn->query($sql);
		} while ($pagesEdge = $fb->next($pagesEdge));
		echo "media insights uploaded successfully";
}

$conn->close();
die;

