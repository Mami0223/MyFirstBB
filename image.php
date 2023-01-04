<?php

include('./setting.php');

$contents_type = array(
    'png'  => 'image/png',
    'jpg'  => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif'  => 'image/gif',
    'bmp'  => 'image/bmp',
    );

//DB接続　 データベース接続情報を引数に渡してPDOクラスのインスタンスを作成
try{
    $dbh = new PDO(DB_DSN, DB_USER, DB_PASS);
}catch(PDOException $e){
    echo $e->getMessage();
}

$id = $_GET["id"];

$sql = 'SELECT imagePath, imageType FROM bb_images_table WHERE id='.$id.';';

// prepareメソッドでプリペアードステートメントを作成
$stmt = $dbh->prepare($sql);

// ここで実際にデータベースに対してクエリが実行され、結果がレコードセットとして記憶される
$stmt->execute();

// fetchAllメソッドで、結果のレコードセットを取得し、最初のレコードを$recordに代入
$record = $stmt->fetchALL(PDO::FETCH_ASSOC)[0];

// 以下は同様にブラウザに出力
$data = file_get_contents($record['imagePath']);
header('Content-type: ' . $contents_type[$record['imageType']]);
echo $data;

//DBの接続を閉じる
$dbh = null;

?>
