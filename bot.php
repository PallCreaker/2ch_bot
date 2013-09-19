<meta content="content" charset="UTF-8"/>
<?php
require_once dirname(__FILE__).'/bot2ch.class.php';

//デバッグ用
//$url = "http://alfalfalfa.com/archives/6575991.html";

$bot2ch = new bot2ch();

$originData = $bot2ch->selectNewPosts( 30 );

$socialAddedData =  $bot2ch->addSocialCount( $originData );

$tagData = $bot2ch->choicePostsData( $socialAddedData );

$tweetData = $bot2ch->sortPost( $tagData );

for ($i=0; $i < 3; $i++) {
	 
	$bot2ch->tagTweetPosts( $tweetData[$i] );
}

echo "<pre>";
var_dump($tweetData);
echo "</pre>";

?>