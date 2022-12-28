<?php

include('./setting.php');

date_default_timezone_set("Asia/Tokyo");

$comment_array = array();
$pdo = null;
$stmt = null;
$error_messages = array();
$postDate = date("Y-m-d H:i:s");
$filePostDate = date("Y-m-d_H-i-s_"); //同名の画像ファイルがアップロードされた際の区別用
$ext = null;
$mami = null;

//DB接続
try {
    $pdo = new PDO(DB_DSN, DB_USER, DB_PASS);
} catch (PDOException $e) {
    echo $e->getMessage();
}

//フォームを打ち込んだとき
if (!empty($_POST["submitButton"])) {
    //名前のチェック
    if (empty($_POST["username"])) {
        echo "名前を入力してください";
        $error_messages["username"] = "名前を入力してください";
    }
    //コメントのチェック
    if (empty($_POST["comment"])) {
        echo "コメントを入力してください";
        $error_messages["comment"] = "コメントを入力してください";
    }

    //画像がある場合、filesディレクトリが存在するか確認して保存（存在しなければディレクトリ作成）
    if (move_uploaded_file($_FILES["upfile"]["tmp_name"], "files/" . $filePostDate . $_FILES["upfile"]["name"])) {
        if (file_exists("files/")) {
            chmod("files/" . $filePostDate . $_FILES["upfile"]["name"], 0644);
            //echo $_FILES["upfile"]["name"] . "をアップロードしました。";
            //移動元のファイルは $_FILES["upfile"]["tmp_name"] 移動先は "files/" . $_FILES["upfile"]["name"] 
        } else {
            if (mkdir("files/", 0777)) {
                chmod("files/" . $filePostDate . $_FILES["upfile"]["name"], 0644);
            } else {
                echo "ディレクトリ作成に失敗しました";
            }
        }
    }


    //画像の拡張子をチェック
    $ext = pathinfo("/files/image.text", PATHINFO_EXTENSION); //拡張子を取得
    setcookie("ext", $ext);
    //$ext = pathinfo($_FILES["upfile"]["name"], PATHINFO_EXTENSION); //拡張子を取得
    if (!($ext == "png" || $ext == "jpg" || $ext == "jpeg"|| $ext == "gif" || $ext == "bmp")) {
        echo "指定された拡張子（png,jpg,jpeg,gif,bmp）のデータをアップロードしてください";
        $error_messages["img"] = "指定された拡張子のデータをアップロードしてください";
    }


    //大サイズのファイルのみアップロードできない場合は、php.iniのupload_max_filesizeを確認してください。


    //エラーメッセージが何もない時だけデータ保存できる
    if (empty($error_messages)) {

        try {
            $stmt = $pdo->prepare('INSERT INTO bb_images_table (username,comment,postDate,imageName,imageType,imageContent,imagePath) 
        VALUES (:username, :comment, :postDate, :imageName, :imageType, :imageContent, :imagePath)');

            $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
            $stmt->bindParam(':comment', $_POST["comment"], PDO::PARAM_STR);
            $stmt->bindParam(':postDate', $postDate, PDO::PARAM_STR);

            $stmt->bindParam(':imageName', $_FILES["upfile"]["name"], PDO::PARAM_STR);
            //$ext = pathinfo($_FILES["upfile"]["name"], PATHINFO_EXTENSION); //拡張子を取得
            $stmt->bindParam(':imageType', $ext, PDO::PARAM_STR);
            $stmt->bindParam(':imageContent', $_FILES["upfile"]["tmp_name"], PDO::PARAM_STR);
            $path = "files/" . $_FILES["upfile"]["name"];
            $stmt->bindParam(':imagePath', $path, PDO::PARAM_STR);

            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    //DBの接続を閉じる
    $pdo = null;
    header('Location: MyFirstBB.php'); //リダイレクトの防止　https://gray-code.com/php/make-the-board-vol23/
    exit;
}

//ページング機能の追加
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
    $hyogi_end_no = $comment_all_num;
} else {
    $hyogi_end_no = $end_no - 1;
}

//DBからコメントデータを取得する
$sql = "SELECT id,username,comment,postDate,imageName,imageType,imageContent,imagePath FROM `bb_images_table` WHERE $start_no <= id && id < $end_no";
$comment_array = $pdo->query($sql);

/*
if ($comment_array) {
    $_SESSION['success_message'] = 'メッセージを書き込みました。';
} else {
    $error_message[] = '書き込みに失敗しました。';
}
*/

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
    <?php if (empty($_POST['btn_submit']) && !empty($_SESSION['success_message'])) : ?>
        <p class="success_message"><?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <hr>

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

                        <?php
                        if (!empty($comment["imageName"])) :
                        ?>
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
                <input type="text" name="username" max="30" 　value="" required>
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
        <?php echo $comment_all_num; ?>件中 <?php echo $start_no; ?> - <?php echo $hyogi_end_no; ?> 件目を表示
    </p>
    <!--戻る-->
    <div class="pagination">
        <?php if ($now >= 2) : ?>
            <a href="index.php?page=<?php echo ($now - 1); ?>" class="page_feed">&laquo;</a>
        <?php else :; ?>
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
            <a href="index.php?page=<?php echo ($now + 1); ?>" class="page_feed">&raquo;</a>
        <?php else : ?>
            <span class="first_last_page">&raquo;</span>
        <?php endif; ?>
    </div>

</body>

</html>
