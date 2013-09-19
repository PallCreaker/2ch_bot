<?php

class bot2ch {
	public function __construct(){
		
		require_once(dirname(__FILE__).'/twitteroauth/twitteroauth.php');
		require_once(dirname(__FILE__).'/config.php');
 
		$this->conn = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, ACCESS_TOKEN, ACCESS_TOKEN_SECRET);
	}
	
	public function connectDB(){
		
		include(dirname(__FILE__).'/db_login.php');
 		$con = mysql_connect($db_host,$db_username,$db_password);
		
		if( !$con ){
			die( 'MySQL接続失敗' );
		}else{
			echo "MySQL接続成功,";
		}
		
		//mysql読み取り時の文字コードを設定
		mysql_query("set names utf8");

		//データベースを選択します	
		$db_select = mysql_select_db($db_databese);
	}
	public function socialCount($url){
		
		$socialCount = array();
		$obj_likes = null;
		
		$source_url = urlencode($url);
		
		$get_facebook = 'http://api.facebook.com/restserver.php?method=links.getStats&urls=' . $source_url;
		$xml = file_get_contents($get_facebook);
		$xml = simplexml_load_string($xml);
		//object(SimpleXMLElement のstringだったのでintに変換。
		$likes = intval( $xml->link_stat->like_count );//いいね！数。※シェア数ならshare_countで
		$fb_total_count = intval( $xml->link_stat->total_count ); // total_count == share_count + like_count
		
		$get_twitter = 'http://urls.api.twitter.com/1/urls/count.json?url=' . $source_url;
		$json = file_get_contents($get_twitter);
		$objData = json_decode($json);
		$tweets = $objData->count;//ツイート数
			
		$get_hatebu = 'http://api.b.st-hatena.com/entry.count?url=' . $source_url;
		$hatebu = file_get_contents($get_hatebu);//はてなブックマーク数
		//stringをintに変換
		$hatebu = intval( $hatebu );
			
		$socialCount = array('url' => $source_url,
							'twitter' => $tweets,
							'likes' => $likes,
							'fb_total_count' =>$fb_total_count,
							'hatebu' => $hatebu
							);
		
		return $socialCount;
	}
	
	public function selectNewPosts( $limit){
		
		$this->connectDB();
		$sql = "SELECT * FROM `rssData` order by id desc LIMIT $limit" ;
		$ret = mysql_query($sql);
		$dbData = array();
		while($col = mysql_fetch_array($ret, MYSQL_ASSOC)) {
 			$dbData[] = array('id' => $col['id'],
 							'time' => $col['time'],
					 		'rss_title' => $col['rss_title'],
					 		'rss_link' => $col['rss_link'],
					 		'site_title' => $col['site_title'],
							'site_link' => $col['site_link'],
				 			'created' => $col['created']
						);
		}
		return $dbData;
	}
	
	
	//こりゃだめだ。たった202この配列でも、ソーシャルボで111しか出来ない。
	public function selectYdayPosts(){
		
		//昨日の日付
		$yDay = date("Y-m-d", strtotime('-1 day'));
	
		$this->connectDB();
		$sql = "SELECT * FROM `rssData` WHERE created LIKE '%$yDay%' order by id desc";//SELECT * FROM 
		$ret = mysql_query($sql);
		$dbData = array();
		while($col = mysql_fetch_array($ret, MYSQL_ASSOC)) {
 			$dbData[] = array('id' => $col['id'],
 							'time' => $col['time'],
					 		'rss_title' => $col['rss_title'],
					 		'rss_link' => $col['rss_link'],
					 		'site_title' => $col['site_title'],
							'site_link' => $col['site_link'],
				 			'created' => $col['created']
						);
		}
		return $dbData;
	}
	
	public function addSocialCount( $origin_Postsdata ){
		
		for ($i=0; $i <count( $origin_Postsdata ) ; $i++) {
			 
			$socialCountsData = $this->socialCount( $origin_Postsdata[$i]['rss_link'] );
			
			//配列を追加する
			$socialPostsData[] = array_merge( $origin_Postsdata[$i], $socialCountsData);
		}
		return $socialPostsData;
	}
	
	public function choicePostsData( $socialPostsData ){
		
		for ($i=0; $i < count( $socialPostsData ); $i++) {
				 
			//初期化
			$tag = array();
			//タイトルに【】がはいってるものはタグをつける。
			$pos = strpos( $socialPostsData[$i]['rss_title'], "【" );
			if( $pos !== FALSE ){
				foreach ($socialPostsData[$i] as $key => $value) {
					$socialPostsData[$i]['tag2'] = TRUE; 
				}
			}else{
				foreach ($socialPostsData[$i] as $key => $value) {
					$socialPostsData[$i]['tag2'] = FALSE; 
				}
			}
			
			if ( $socialPostsData[$i]['twitter'] == 0 && $socialPostsData[$i]['fb_total_count'] == 0 && $socialPostsData[$i]['hatebu'] == 0 ) {
				$tag = array("tag" => "《残念》",
							 "tag_num" => 0
							 );
			}
		//	if ( $socialPostsData[$i]['twitter'] > 30 ) {
			//	$tag = array("tag" => "《いいね！》");
			//}
			if ( $socialPostsData[$i]['twitter'] > 100 ) {
				$tag = array("tag" => "《シェア多数》",
							 "tag_num" => 1
							);
			}
			if( $socialPostsData[$i]['fb_total_count'] > 10) {
				$tag = array("tag" => "《読んどいた方がいいかも！》",
							 "tag_num" => 2
							 );
			}
			if( $socialPostsData[$i]['twitter'] > 150) {
				$tag = array("tag" => "《これは読んどけ》",
							 "tag_num" => 3
							 );
			}
			if( $socialPostsData[$i]['twitter'] > 250) {
				$tag = array("tag" => "《話題沸騰中》",
							 "tag_num" => 4
							 );
			}
			if( $socialPostsData[$i]['hatebu'] > 20) {
				$tag = array("tag" => "《バズってる》",
							 "tag_num" => 5
							 );
			}
			if( $socialPostsData[$i]['hatebu'] > 50  &&  $socialPostsData[$i]['twitter'] > 200  &&  $socialPostsData[$i]['fb_total_count'] > 30) {
				$tag = array("tag" => "《超バズってる》",
							 "tag_num" => 6
							 );
			}
			
			
			$socialPostsData[$i] = array_merge( $socialPostsData[$i], $tag );
		}
		return $socialPostsData;
	}

	public function sortPost($data){
		
		// 列方向の配列を得る
		foreach ($data as $key => $row) {
		    $volume[$key]  = $row['tag_num'];
		}
		// データを volume の降順、edition の昇順にソートする。
		// $data を最後のパラメータとして渡し、同じキーでソートする。
		array_multisort($volume, SORT_DESC, $data);  //SORT_DESC 降順   SORT_ASC 昇順
		
		return $data;
	}
	
	public function tagTweetPosts($tagsData){
		
		if ( ($tagsData['tag2']) !== FALSE ) {
			if (!is_null($tagsData['tag'])) {
				$Cmsg = $tagsData['tag'] . $tagsData['rss_title'] . '=>' . $tagsData['rss_link'];
			}else{
				$Cmsg = $tagsData['rss_title'] . '=>' . $tagsData['rss_link'];
			}
		}else{
			if (!is_null($tagsData['tag'])) {
				$Cmsg = $tagsData['tag'] . $tagsData['rss_title'] . '=>' . $tagsData['rss_link'];
			}else{
				$action = "skip";
			}
		}
		/*
		if ( mb_strlen($msg) > 140 ) {
			echo mb_strlen($msg);
			$overCount = mb_strlen($msg) - 142 ;
			$originCount = mb_strlen( $rssData['rss_title'] );
			$deleteCount = $originCount - $overCount;
			
			$Cmsg = mb_substr( $rssData['rss_title'], 0, $deleteCount);
		}*/
		if ( $action == "skip" ) {
			$result = $action;
		}else{
			$params = array(
					   		'status' => $Cmsg 
					   		);
			//$result = $this->conn->post('statuses/update', $params);
			$result = $this->conn->OAuthRequest('http://api.twitter.com/1.1/statuses/update.json', 'POST', array('status' => $Cmsg));
		}
		
		$pos = strpos($result, "Status is a duplicate");
		
		if ( $pos !== FALSE ) {
			echo "これが、重複です=>(".$Cmsg.")";
			echo "<br />";
		}elseif(!is_null($action)){
			echo "skip";
			echo "<br />";
		}else{
			echo "tweets文=>(".$Cmsg.")";
			echo "<br />";
		}
		
	}
	
	public function tweets($rssData){
		$Cmsg = "《新着》". $rssData['rss_title'] . '=>' . $rssData['rss_link']." #2ch";
	/*	if ( mb_strlen($msg) > 140 ) {
			$overCount = mb_strlen($msg) - 142 ;
			$originCount = mb_strlen( $rssData['rss_title'] );
			$deleteCount = $originCount - $overCount;
			
			$Cmsg = mb_substr( $rssData['rss_title'], 0, $deleteCount);
		}else{
			continue;
		}*/
		$params = array(
				   		'status' => $Cmsg 
				   		);
		//$result = $this->conn->post('statuses/update', $params);//旧API
		$result = $this->conn->OAuthRequest('http://api.twitter.com/1.1/statuses/update.json', 'POST', array('status' => $Cmsg));
		echo "<pre>";
		var_dump($result);
		echo "</pre>";
		$pos = strpos($result, "Status is a duplicate");
		if ( $pos !== FALSE ) {
			echo "これが、重複です=>(".$Cmsg.")";
		}
	}
}
?>