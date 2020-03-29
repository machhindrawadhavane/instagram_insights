<?php
$start = microtime(true);
$startTimeQuery = '';
require_once __DIR__ . '/vendor/autoload.php';
require "database_connection.php";
require "db_config.php";
$link = new Database(DB_SERVER, DB_USER, DB_PASS, DB_DATABASE);
$conn = $link->connect();
$fb = new \Facebook\Facebook([
    'app_id' => '2146019202099159',
    'app_secret' => 'a20970d9eb60dc9e03fec9ff637934f2',
    'default_graph_version' => 'v6.0',
    //'default_access_token' => '{access-token}', // optional
]);

$app_token = 'EAAefywg459cBADfccNWlIMtkW6ZBpNA0KRfJc0UcHltaJVfbr9wYbyIGd68Dhcf7EsFBu6mrZBlKZCfPIyTf9zCS2mfFvpSLJ1Y4fWa7ZAzmzvjOm4jK6fHM0ZCrMAqBytf9nqNUC4QZCPBdl5PtJZBpphGsccVRtbRR7OsA9ZB7rAZDZD';
$pageId = '1954072204870360';
$instagramBusinessAccountId = '17841407454307610';
function checkTokenValidity($token)
{
    global $fb;
    $oauth = $fb->getOAuth2Client();
    $meta = $oauth->debugToken($token);
    return $meta->getIsValid();
}

checkTokenValidity($app_token);
getDataFromFacebook($app_token);

function getDataFromFacebook($token)
{
    global $conn,$fb,$pageId,$instagramBusinessAccountId;
	$url = $instagramBusinessAccountId.'/media';
	//$url = $instagramBusinessAccountId.'/insights?metric=impressions,reach,profile_views&period=day';
	try {
			$resp = $fb->get( $url, $token);
		} catch (Facebook\Exceptions\FacebookResponseException $e) {
			echo 'Graph returned an error: ' . $e->getMessage();
			exit;
		} catch (Facebook\Exceptions\FacebookSDKException $e) {
			echo 'Facebook SDK returned an error: ' . $e->getMessage();
			exit;
		}
		
		print_r($resp);
		
}
die;
