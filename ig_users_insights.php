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

$app_token = 'EAAcqvrpUGOQBAMU1oZAGZAPbXIeBBuAFZAMHNUDeNYdjAiCnYvlUkAp7VJI5yy4h7VnlpleL0GXrObMCbURdVSd0FvHX0zNLpdZBYX4vwSOw4Emoh3ZCYyTMyTe2VYmGPKZBdZAjPbquB9YedBwqAkvi5lyF9euM82IHMy2FbZBheAZDZD';
$pageId = '1954072204870360';
$pageName = "NEWJ";

function generateDates($start, $end)
{
  $result = [];
  while ($start <= $end) {
    $result[$start->format('Y')][$start->format('m')][] = $start->format('d');
    $start->add(new DateInterval('P1D'));
  }
  $monthsArray  = array();
  foreach($result as $year => $yearlyData){
	  foreach($yearlyData as $monthName => $mothData){
		  $count = count($mothData);
		  $monthsArray[$year][$monthName]['start_date'] = $year.'-'.$monthName.'-'.$mothData[0];
		  $monthsArray[$year][$monthName]['end_date'] = $year.'-'.$monthName.'-'.$mothData[$count-1];
	  }
  }
  return $monthsArray;
}

$start = new DateTime('2019-01-1');
$end = new DateTime('2019-12-30');

$yearlyMonthData = generateDates($start, $end);
$instagramBusinessAccountId = '17841407454307610';
$query = "select * from ig_statuses where id = 2 ";
$result = mysqli_query($conn,$query);
$pageInsightsStatusData = mysqli_fetch_object($result);
$pageId = $pageInsightsStatusData->page_id;
$pageName = $pageInsightsStatusData->page_name;
$instagramBusinessAccountId = $pageInsightsStatusData->ig_business_account_id;
$since = $pageInsightsStatusData->start_date;
$until = $pageInsightsStatusData->end_date;

$isUpdateIGStatuses = false;

function checkTokenValidity($token)
{
    global $fb;
    $oauth = $fb->getOAuth2Client();
    $meta = $oauth->debugToken($token);
    return $meta->getIsValid();
}

checkTokenValidity($app_token);
foreach($yearlyMonthData as $yearlyData){
	foreach($yearlyData as $monthData){
		$since = $monthData['start_date'];
		$until = $monthData['end_date'];	
		postInstagramUserDaysInsightsDataByAccountID($app_token);
		die;
	}
}

if($isUpdateIGStatuses == true){
	updateInstagramStatusesData($app_token);
}

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
    global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName,$since,$until;
	$url = $instagramBusinessAccountId.'/insights?metric=impressions,reach,profile_views,email_contacts,follower_count,get_directions_clicks,phone_call_clicks,text_message_clicks,website_clicks&period=day&since='.$since.'&until='.$until.' ';
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
		//print_r($pagesEdge);die;
		do {
			$monthCounter = 0;
			$innerPostDataArr = array();
			foreach($pagesEdge as $userInsights){
				$updateQueryCoulumnValues = "period='day' ";
				if(isset($userInsights['values']) && count($userInsights['values']) > 0){
					foreach($userInsights['values'] as $key => $dateWiseData){
						$dt = new DateTime();
						$date_object = $dateWiseData['end_time'];
						$insightDate = $date_object->format('Y-m-d');	
						$metricName = isset($userInsights['name']) ? mysqli_real_escape_string($conn,$userInsights['name']) : "";
						$value = isset($dateWiseData['value']) ? $dateWiseData['value'] : 0;
						$innerPostDataArr[$insightDate][$metricName]['value'] = $value;
						$innerPostDataArr[$insightDate]['ig_business_account_id']['value'] = $instagramBusinessAccountId;
						$innerPostDataArr[$insightDate]['page_id']['value'] = $pageId;
						$innerPostDataArr[$insightDate]['page_name']['value'] = $pageName;
						$innerPostDataArr[$insightDate]['period']['value'] = $userInsights['period'];
					}
				}
				$monthCounter++;
			}
			insertUpdateMediaInsighs($innerPostDataArr);
		} while ($pagesEdge = $fb->next($pagesEdge));
}

function insertUpdateMediaInsighs($igMediaInsightsData=array()){
	 global $conn,$fb,$pageId,$instagramBusinessAccountId,$pageName;
	foreach($igMediaInsightsData as $insightsDate => $fieldsData){
		$insertQueryColumns = '`date`';
		$vls = "'".$insightsDate."' ";
		$updateQueryCoulumnValues = "date='".$insightsDate."'";
		foreach($fieldsData as $columnName=>$innerData){
			$insertQueryColumns.=',`'.$columnName.'`';
			$value = isset($innerData['value']) ? $innerData['value'] : 0;
			$updateQueryCoulumnValues.=",".$columnName."='".$value."' ";
			$vls .= ",'".$value."' ";
		}
		$query = "select * from ig_users_post_insights_data_daywise where ig_business_account_id = '".$fieldsData['ig_business_account_id']['value']."' and date = '".$insightsDate."' ";
		$result = mysqli_query($conn, $query);
		$rowcount=mysqli_num_rows($result);
		if($rowcount > 0){
			$sql = "UPDATE ig_users_post_insights_data_daywise SET $updateQueryCoulumnValues WHERE ig_business_account_id = '".$fieldsData['ig_business_account_id']['value']."' and date = '".$insightsDate."' ";
		}else{
			$sql = "INSERT INTO ig_users_post_insights_data_daywise (".$insertQueryColumns.") VALUES (".$vls.") ";
		}
		$updateStatusQuery = "UPDATE ig_statuses SET start_date='".$insightsDate."' WHERE ig_business_account_id = '".$fieldsData['ig_business_account_id']['value']."' ";
		echo $updateStatusQuery."\n";
		$conn->query($updateStatusQuery);
		$conn->query($sql);
	}
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
				  $sql = "UPDATE ig_statuses SET access_token='".$bAccounts['access_token']."',ig_business_account_id='".$igBusinessAccountID."' WHERE page_id = '".$bAccounts['id']."' ";
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
