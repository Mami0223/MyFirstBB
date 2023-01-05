<?php

include('./setting.php');

$comment_array = array();
$pdo = null;
$stmt = null;
$postDate = date("Y-m-d H:i:s");
$filePostDate = date("Y-m-d_H-i-s_"); //同名の画像ファイルがアップロードされた際の区別用
$ext = null;

//DB接続
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
} catch (PDOException $e) {
    error_log("DBに接続できませんでした", 3, "./error.log");
}


/////////////////////////
// ページング機能の追加  //
/////////////////////////

$MAX = 10; //1頁に表示するコメントの最大数は10個
if (!isset($_GET['page'])) { // $_GET['page_id'] はURLに渡された現在のページ数
    $now = 1; // 設定されてない場合は1ページ目にする
} else {
    $now = $_GET['page'];
}

$start_no = ($now - 1) * $MAX + 1; // DBの何番目から取得すればよいか
$end_no = $start_no + $MAX;

//alldata数をカウント用
$sql_all_num = "SELECT id FROM `bb_images_table` ";
$comment_array_all_num = $pdo->query($sql_all_num);
$comment_all_num = $comment_array_all_num->rowCount(); //トータルdata件数
$max_page = ceil($comment_all_num / $MAX);

//page番号をつくる
if ($now == 1 || $now == $max_page) {
    $range = 4;
} elseif ($now == 2 || $now == $max_page - 1) {
    $range = 3;
} else {
    $range = 2;
}

//一番下の表示用
if ($end_no > $comment_all_num) {
    $hyoji_end_no = $comment_all_num;
} else {
    $hyoji_end_no = $end_no - 1;
}


/////////////////////////
// フォーム入力をDB保存  //
/////////////////////////

//フォームを打ち込んだとき
if ((!empty($_POST["submitButton"]))) {
    //名前のチェック
    if (preg_match('/^\s*$/u', $_POST["username"])) {
        error_log("名前を入力してください（空白不可）", 3, "./error.log");
    }
    //コメントのチェック
    if (preg_match('/^\s*$/u', $_POST["comment"])) {
        error_log("コメントを入力してください（空白不可）", 3, "./error.log");
    }

    //画像保存用のfilesディレクトリが存在しなければディレクトリ作成
    if (!(file_exists("files/"))) {
        mkdir("files/", 0777);
    }

    //添付可能な画像データサイズは5MBまで
    if ($_FILES["upfile"]["size"] >= 5 * 1024 * 1024) {
        error_log("ファイルの添付可能サイズは最大5MBです", 3, "./error.log");
    } else {
        //画像が添付された場合、拡張子をチェック
        if (move_uploaded_file($_FILES["upfile"]["tmp_name"], "files/" . $filePostDate . $_FILES["upfile"]["name"])) {
            chmod("files/" . $filePostDate . $_FILES["upfile"]["name"], 0644);
            //移動元のファイルは $_FILES["upfile"]["tmp_name"] 移動先は "files/" . $_FILES["upfile"]["name"]

            //画像の拡張子をチェック
            $ext = pathinfo($_FILES["upfile"]["name"], PATHINFO_EXTENSION); //拡張子を取得
            if (!($ext == "png" || $ext == "jpg" || $ext == "jpeg" || $ext == "gif" || $ext == "bmp")) {
                error_log("指定された拡張子（png,jpg,jpeg,gif,bmp）のデータをアップロードしてください", 3, "./error.log");
            }
        }
    }

    //エラーメッセージが何もない時だけデータ保存できる
    if (!(file_exists("./error.log"))) {
        try {
            $stmt = $pdo->prepare('INSERT INTO bb_images_table (username,comment,postDate,imageName,imageType,imagePath) 
        VALUES (:username, :comment, :postDate, :imageName, :imageType, :imagePath)');

            //SQLインジェクション・クロスサイトスクリプティング対策
            $usernameSpecialChars = htmlspecialchars($_POST['username'], ENT_QUOTES, "UTF-8");
            $stmt->bindParam(':username', $usernameSpecialChars, PDO::PARAM_STR);
            $commentSpecialChars = htmlspecialchars($_POST['comment'], ENT_QUOTES, "UTF-8");
            $stmt->bindParam(':comment', $commentSpecialChars, PDO::PARAM_STR);

            $stmt->bindParam(':postDate', $postDate, PDO::PARAM_STR);
            $stmt->bindParam(':imageName', $_FILES["upfile"]["name"], PDO::PARAM_STR);
            $ext = pathinfo($_FILES["upfile"]["name"], PATHINFO_EXTENSION); //拡張子を取得(既述のextは画像添付しない場合に読み込まれないのでここでextの再定義必須)
            $stmt->bindParam(':imageType', $ext, PDO::PARAM_STR);
            $path = "files/" . $filePostDate. $_FILES["upfile"]["name"];
            $stmt->bindParam(':imagePath', $path, PDO::PARAM_STR);

            $stmt->execute();
        } catch (PDOException $e) {
            error_log("データ保存できませんでした", 3, "./error.log");
        }
    }

    //DBの接続を閉じる
    $pdo = null;

    //最新投稿の存在するページを開く
    if ($comment_all_num == $max_page * $MAX) {
        $new_max_page = $max_page + 1;
        $link = "Location: MyFirstBB.php?page={$new_max_page}";
    } else {
        $link = "Location: MyFirstBB.php?page={$max_page}";
    }
    header($link); //リロードによる再送信を防止するためのリダイレクト　https://gray-code.com/php/make-the-board-vol23/
    exit;
}

//DBからコメントデータを取得する
$sql = "SELECT id,username,comment,postDate,imageName,imageType,imagePath FROM `bb_images_table` WHERE $start_no <= id && id < $end_no";
$comment_array = $pdo->query($sql);

//エラーログファイルが存在する場合は、アラートを出す
if (file_exists("./error.log")) {
    $errorLog = file_get_contents("./error.log");
    $alert = "<script type='text/javascript'>alert('". $errorLog ."');</script>";
    echo $alert;
    unlink("./error.log");//エラーログファイルを削除
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>PHP掲示板</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <h1 class="title">PHPとMySQLで掲示板</h1>

    <div class="boardWrapper">
        <section>
            <?php
            foreach ($comment_array as $comment) :
                $imagesrc = "image.php?id=" . $comment["id"];
            ?>
            <article>
                <div class="wrapper">
                    <div class="nameArea">
                        <span class="id"><?php echo $comment["id"]; ?></span>
                        <span>名前：</span>
                        <p class="username"><?php echo $comment["username"]; ?></p>
                        <time>:<?php echo $comment["postDate"]; ?></time>
                    </div>
                    <p class="comment"><?php echo $comment["comment"]; ?></p>

                    <?php if (!empty($comment["imageName"])) :?>
                        <img src="<?php echo $imagesrc ?>" , width="250">
                    <?php endif; ?>
                </div>
            </article>
            <?php endforeach; ?>
        </section>
        <form class="formWrapper" action="" enctype="multipart/form-data" method="POST">
            <!--actionの中身は空にするhttps://style.potepan.com/articles/20409.html#action82218221-->

            <div>
                <input type="submit" value="書き込む" name="submitButton">
                <label for="">名前：</label>
                <input type="text" name="username" maxlength="30" value="" required>
            </div>
            <div>
                <textarea class="commentTextArea" name="comment" value="" required></textarea>
            </div>
            <div>
                添付ファイル：<br />
                <input type="file" name="upfile" size="30" /><br />
                <br />
            </div>

        </form>
    </div>

    <!-- ページ移動 -->
    <p class="from_to">
        <?php echo $comment_all_num; ?>件中 <?php echo $start_no; ?> - <?php echo $hyoji_end_no; ?> 件目を表示
    </p>

    <div class="pagination">
        <!--戻る-->
        <?php if ($now >= 2) : ?>
            <a href="MyFirstBB.php?page=<?php echo($now - 1); ?>" class="page_feed">&laquo;</a>
        <?php else: ?>
            <span class="first_last_page">&laquo;</span>
        <?php endif; ?>

        <!--ページ番号-->
        <?php for ($i = 1; $i <= $max_page; $i++) : ?>
            <?php if ($i >= $now - $range && $i <= $now + $range) : ?>
                <?php if ($i == $now) : ?>
                    <span class="now_page_number"><?php echo $i; ?></span>
                <?php else : ?>
                    <a href="?page=<?php echo $i; ?>" class="page_number"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endif; ?>
        <?php endfor; ?>

        <!--進む-->
        <?php if ($now < $max_page) : ?>
            <a href="MyFirstBB.php?page=<?php echo($now + 1); ?>" class="page_feed">&raquo;</a>
        <?php else : ?>
            <span class="first_last_page">&raquo;</span>
        <?php endif; ?>
    </div>

</body>

</html>
