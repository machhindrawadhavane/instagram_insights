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
$isSaveUpdateMediaPostInsights = true;
$isSaveUpdateMediaIds = false;
$app_token = 'EAAcqvrpUGOQBALupNk6eEIiT7Fq9ZB1tpLt2zhVdwyZAN49IKzes35BobVMCZCwOwi2QWKMKfTH2ysKui8ZBoE51qaPVWGDLzuRbZCqmGb7hgP5C6v03J0ai9u1ZBm49GOxN2syviZBkIM8xc6Tt21WMKklJKWIkd4ExBc31kZCLRy7IcrD937YMWp0m2N3gjN5Qevycs07fuQZDZD';
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
	//saveUpdateInstagramMediaIdFromBusinessAccountId($app_token);
}

if($isSaveUpdateMediaPostInsights == true){
	$query = "select * from ig_statuses where ig_business_account_id != '0' ";
	$result = mysqli_query($conn,$query);
	while($igStatusData = mysqli_fetch_assoc($result)){
		$pageName = isset($igStatusData['page_name']) ? $igStatusData['page_name'] : "NEWJ";
		$queryi = "select * from ig_media_ids where page_name = '".$pageName."' ";
		$resultData = mysqli_query($conn, $queryi);
		while ($mediaData = mysqli_fetch_assoc($resultData)) {
			postInstagramMediaInsightsDataLifeTimeByMediaId($mediaData,$app_token);
		}
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
				postInstagramMediaMetaDataByMediaId($mediaData,$token);
			}
		} while ($pagesEdge = $fb->next($pagesEdge));
}

function postInstagramMediaMetaDataByMediaId($mediaData = array(),$token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	$url = $mediaData['id'].'?fields=caption,children,comments_count,ig_id,media_type,media_url,owner,permalink,shortcode,timestamp,like_count';
	try {
			$resp = $fb->get($url,$token);
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		$pagesEdge = $resp->getGraphNode();
		$media_url = isset($pagesEdge['media_url']) ? $pagesEdge['media_url'] : null;
		$updateQueryCoulumnValues = "caption='".$pagesEdge['caption']."',media_type='".$pagesEdge['media_type']."',permalink='".$pagesEdge['permalink']."',media_url='".$media_url."',shortcode='".$pagesEdge['shortcode']."',timestamp='".$pagesEdge['timestamp']."',like_count='".$pagesEdge['like_count']."',comments_count='".$pagesEdge['comments_count']."' ";
		$sql = "UPDATE ig_media_ids SET $updateQueryCoulumnValues WHERE ig_media_id = '".$mediaData['id']."' ";
		//echo $mediaData['id'].'<\n>';
		$conn->query($sql);
}

function postInstagramMediaInsightsDataLifeTimeByMediaId($mediaData = array(),$token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	if(isset($mediaData['media_type']) && $mediaData['media_type'] == "VIDEO"){
		$url = $mediaData['ig_media_id'].'/insights?metric=engagement,impressions,reach,saved,video_views&period=lifetime';
	}else{
		$url = $mediaData['ig_media_id'].'/insights?metric=engagement,impressions,reach,saved&period=lifetime';
	}
	
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
			echo $mediaData['ig_media_id'].'</n>';
			$conn->query($sql);
		} while ($pagesEdge = $fb->next($pagesEdge));
}

$conn->close();
echo "data updated successfully";
die;

