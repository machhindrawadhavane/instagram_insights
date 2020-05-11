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
    'app_id' => '2017323495200996',
    'app_secret' => 'acfeadb315700871e3c39ae4d731c2c5',
    'default_graph_version' => 'v6.0',
    //'default_access_token' => '{access-token}', // optional
]);
$isSaveUpdateMediaIds = true;
$app_token = 'EAAcqvrpUGOQBAMU1oZAGZAPbXIeBBuAFZAMHNUDeNYdjAiCnYvlUkAp7VJI5yy4h7VnlpleL0GXrObMCbURdVSd0FvHX0zNLpdZBYX4vwSOw4Emoh3ZCYyTMyTe2VYmGPKZBdZAjPbquB9YedBwqAkvi5lyF9euM82IHMy2FbZBheAZDZD';
$pageId = '100589847991303';
$pageName = "English NEWJ";
$instagramBusinessAccountId = '17841419587757276';
function checkTokenValidity($token)
{
    global $fb;
    $oauth = $fb->getOAuth2Client();
    $meta = $oauth->debugToken($token);
    return $meta->getIsValid();
}
checkTokenValidity($app_token);

if($isSaveUpdateMediaIds == true){
	$query = "select * from ig_statuses where ig_business_account_id != '0' ";
	$result = mysqli_query($conn,$query);
	while($igStatusData = mysqli_fetch_assoc($result)){
		$pageName = isset($igStatusData['page_name']) ? $igStatusData['page_name'] : "NEWJ";
		$pageId = isset($igStatusData['page_id']) ? $igStatusData['page_id'] : "1954072204870360";
		$instagramBusinessAccountId = isset($igStatusData['ig_business_account_id']) ? $igStatusData['ig_business_account_id'] : "17841407454307610";
		saveUpdateInstagramMediaIdFromBusinessAccountId($app_token);
	}
}

function saveUpdateInstagramMediaIdFromBusinessAccountId($token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	$url = $instagramBusinessAccountId.'/stories';
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
				$query = "select * from ig_media_story_insights where ig_media_id = '".$mediaData['id']."' ";
				$result = mysqli_query($conn, $query);
				$rowcount=mysqli_num_rows($result);
				if($rowcount > 0){
				   $sql = "UPDATE ig_media_story_insights SET ig_media_id='".$mediaData['id']."' WHERE ig_media_id = '".$mediaData['id']."' ";
				}else{
				   $sql = "INSERT INTO `ig_media_story_insights` (page_id,ig_business_account_id,page_name,ig_media_id) VALUES ('".$pageId."','".$instagramBusinessAccountId."','".$pageName."','".$mediaData['id']."') ";
				}
				$conn->query($sql);
				postInstagramMediaStoryInsights($mediaData,$token);
			}
		} while ($pagesEdge = $fb->next($pagesEdge));
}


function postInstagramMediaStoryInsights($mediaData = array(),$token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	$url = $mediaData['id'].'/insights?metric=exits,replies,impressions,reach,replies,taps_forward,taps_back';
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
			$vls = "'".$pageId."','".$instagramBusinessAccountId."','".$pageName."','".$mediaData['id']."' ";
			$insertQueryColumns = '`page_id`,`ig_business_account_id`,`page_name`,`ig_media_id`';
			$updateQueryCoulumnValues = "page_id='".$pageId."',ig_business_account_id='".$instagramBusinessAccountId."',page_name='".$pageName."',ig_media_id='".$mediaData['id']."' ";
			foreach($pagesEdge as $userInsights){
				$insertQueryColumns.= ',`'.$userInsights['name'].'`';
				$value = isset($userInsights['values'][0]['value']) ? $userInsights['values'][0]['value'] : 0;
				$updateQueryCoulumnValues.= ",".$userInsights['name']."='".$value."' ";
				$vls .= ",'".$value."' ";
			}
			$query = "select * from ig_media_story_insights where ig_media_id = '".$mediaData['id']."' ";
			$result = mysqli_query($conn, $query);
			$rowcount=mysqli_num_rows($result);
			if($rowcount > 0){
				$sql = "UPDATE ig_media_story_insights SET $updateQueryCoulumnValues WHERE ig_media_id = '".$mediaData['id']."' ";
			}else{
				$sql = "INSERT INTO ig_media_story_insights (".$insertQueryColumns.") VALUES (".$vls.") ";
			}
			echo $mediaData['id'].'</n>';
			$conn->query($sql);
		} while ($pagesEdge = $fb->next($pagesEdge));
}

$conn->close();
echo "data updated successfully";
die;

