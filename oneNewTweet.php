<meta content="content" charset="UTF-8"/>
<?php
require dirname(__FILE__).'/bot2ch.class.php';

//デバッグ用
//$url = "http://alfalfalfa.com/archives/6575991.html";

$bot2ch = new bot2ch();

$originData = $bot2ch->selectNewPosts( 2 );

$bot2ch->tweets( $originData[0] );