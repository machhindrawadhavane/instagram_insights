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
updateInstagramStatusesData($app_token);

function saveUpdateInstagramMediaIdFromBusinessAccountId($token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId;
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
		$resp = $resp->getDecodedBody();
}


function postInstagramUserDaysInsightsDataByAccountID($token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	$url = $instagramBusinessAccountId.'/insights?metric=impressions,reach,profile_views,email_contacts,follower_count,get_directions_clicks,phone_call_clicks,text_message_clicks,website_clicks&period=day';
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
			foreach($pagesEdge as $userInsights){
				if(isset($userInsights['values']) && count($userInsights['values']) > 0){
					foreach($userInsights['values'] as $dateWiseData){
						$dt = new DateTime();
						$date_object = $dateWiseData['end_time'];
						$insightDate = $date_object->format('Y-m-d');	
						$metricName = isset($userInsights['name']) ? mysqli_real_escape_string($conn,$userInsights['name']) : "";
						$query = "select * from ig_users_post_insights_data_daywise where ig_business_account_id = '".$instagramBusinessAccountId."' and metric_name = '".$metricName."' and date = '".$insightDate."' ";
						$result = mysqli_query($conn, $query);
						$rowcount=mysqli_num_rows($result);
						if($rowcount > 0){
							 $sql = "UPDATE ig_users_post_insights_data_daywise SET value ='".$dateWiseData['value']."' WHERE ig_business_account_id = '".$instagramBusinessAccountId."' and metric_name = '".$metricName."' and date = '".$insightDate."' ";
						}else{
								$sql = "INSERT INTO `ig_users_post_insights_data_daywise` (page_id,ig_business_account_id,page_name,metric_name,period,value,date) VALUES ('".$pageId."','".$instagramBusinessAccountId."','".$pageName."','".$metricName."','".$userInsights['period']."','".$dateWiseData['value']."','".$insightDate."') ";
						}
						$conn->query($sql);
					}
				}
			}
		} while ($pagesEdge = $fb->next($pagesEdge));
}

function updateInstagramStatusesData($token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId;
	$url = 'me/accounts';
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
			foreach($pagesEdge as $bAccounts){
				$bAccounts->asArray();
				$query = "select * from ig_statuses where page_id = '".$bAccounts['id']."' ";
				$result = mysqli_query($conn, $query);
				$rowcount=mysqli_num_rows($result);
				$igBusinessAccountID = getInstagramBusinessAccountIdByPageId($token,$bAccounts['id']);
				if($rowcount > 0){
				  echo $sql = "UPDATE ig_statuses SET access_token='".$bAccounts['access_token']."',ig_business_account_id='".$igBusinessAccountID."' WHERE page_id = '".$bAccounts['id']."' ";
				}else{
				   $sql = "INSERT INTO `ig_statuses` (access_token,page_id,ig_business_account_id,page_name) VALUES ('".$bAccounts['access_token']."','".$bAccounts['id']."','".$igBusinessAccountID."','".$bAccounts['name']."') ";
				}
				echo $sql;
				$conn->query($sql);
			}
		} while ($pagesEdge = $fb->next($pagesEdge));
}

function getInstagramBusinessAccountIdByPageId($token,$pageId)
{
    global $conn,$fb,$instagramBusinessAccountId;
	$url = $pageId.'?fields=instagram_business_account';
	try {
			$resp = $fb->get($url,$token);
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		$resp = $resp->getDecodedBody();
		if(isset($resp['instagram_business_account']['id'])){
			return $resp['instagram_business_account']['id'];
		}
		return 0;
}

$conn->close();
die;
