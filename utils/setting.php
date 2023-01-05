<?php

date_default_timezone_set("Asia/Tokyo");

//user, password, databaseName, databaseNameの4か所は環境に併せて設定してください
define( 'DB_HOST', 'localhost' );
define( 'DB_USER', 'user' );
define( 'DB_PASS', 'password' );
define( 'DB_NAME', 'databaseName' );
define( 'DB_DSN', 'mysql:host=localhost;dbname=databaseName' );

?>
