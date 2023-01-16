<?php

include('setting.php');
include('function.php');

$contents_type = array(
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'bmp' => 'image/bmp',
);

$image_error_messages = array();

//DB接続　 データベース接続情報を引数に渡してPDOクラスのインスタンスを作成
try {
    $dbh = new PDO(DB_DSN, DB_USER, DB_PASS);
} catch (PDOException $e) {
    echo $e->getMessage();
}

$id = $_GET["id"];

if (is_numeric($id)) {//文字が数値として有効な値であれば
    try {
        $sql = 'SELECT imagePath, imageType FROM bb_images_table WHERE id= :id';

        // prepareメソッドでプリペアードステートメントを作成
        $stmt = $dbh->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        // ここで実際にデータベースに対してクエリが実行され、結果がレコードセットとして記憶される
        $stmt->execute();
    } catch (PDOException $e) {
        $image_error_messages["save"] = "データ保存できませんでした";
        myErrorLog("エラー種別:データ保存エラー");
    }
}else{
    $image_error_messages["id"] = "idへは数値を入力してください";
    myErrorLog("エラー種別:idへの入力値が数値として無効");
}

//エラーが存在する場合は、アラートを出す
if ($image_error_messages) {
    $alert = "<script>alert('". implode(" ", $image_error_messages) ."');</script>";
    echo $alert;
    echo '<script>location.href = "../MyFirstBB.php" ;</script>';
    exit;
}

// fetchAllメソッドで、結果のレコードセットを取得し、最初のレコードを$recordに代入
$record = $stmt->fetchALL(PDO::FETCH_ASSOC)[0];

// 以下は同様にブラウザに出力
$data = file_get_contents('../' . $record['imagePath']);
header('Content-type: ' . $contents_type[$record['imageType']]);
echo $data;

//DBの接続を閉じる
$dbh = null;
