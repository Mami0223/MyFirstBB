<?php

include('./setting.php');

date_default_timezone_set("Asia/Tokyo");


$comment_array = array();
$pdo = null;
$stmt = null;
$error_messages = array();


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

    //画像がある場合、filesディレクトリに保存
    if (move_uploaded_file($_FILES["upfile"]["tmp_name"], "files/" . $_FILES["upfile"]["name"])) {
    chmod("files/" . $_FILES["upfile"]["name"], 0644);
    //echo $_FILES["upfile"]["name"] . "をアップロードしました。";
    //移動元のファイルは $_FILES["upfile"]["tmp_name"] 移動先は "files/" . $_FILES["upfile"]["name"] 
    }


    //エラーメッセージが何もない時だけデータ保存できる
    if (empty($error_messages)) {
        $postDate = date("Y-m-d H:i:s");

        try {
            $stmt = $pdo->prepare('INSERT INTO bb_images_table (username,comment,postDate,imageName,imageType,imageContent,imagePath) 
        VALUES (:username, :comment, :postDate, :imageName, :imageType, :imageContent, :imagePath)');

            $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
            $stmt->bindParam(':comment', $_POST["comment"], PDO::PARAM_STR);
            $stmt->bindParam(':postDate', $postDate, PDO::PARAM_STR);

            $stmt->bindParam(':imageName', $_FILES["upfile"]["name"], PDO::PARAM_STR);
            $ext = pathinfo($_FILES["upfile"]["name"], PATHINFO_EXTENSION); //拡張子を取得
            $stmt->bindParam(':imageType', $ext, PDO::PARAM_STR);
            $stmt->bindParam(':imageContent', $_FILES["upfile"]["tmp_name"], PDO::PARAM_STR);
            $path = "files/" . $_FILES["upfile"]["name"];
            $stmt->bindParam(':imagePath', $path, PDO::PARAM_STR);

            $stmt->execute();

        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }


    
    if($comment_array){
        $_SESSION['success_message'] = 'メッセージを書き込みました。';
    }else{
        $error_message[] = '書き込みに失敗しました。';
    }

    //DBの接続を閉じる
    $pdo = null;
    header('Location: MyFirstBB.php');//リダイレクトの防止　https://gray-code.com/php/make-the-board-vol23/
    exit;
}

//DBからコメントデータを取得する
$sql = "SELECT id,username,comment,postDate,imageName,imageType,imageContent,imagePath FROM `bb_images_table` ";
$comment_array = $pdo->query($sql);

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
    <?php if( empty($_POST['btn_submit']) && !empty($_SESSION['success_message']) ): ?>
	<p class="success_message"><?php echo htmlspecialchars( $_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?></p>
	<?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <hr>
    <div class="boardWrapper">
        <section>
            <?php 
            foreach($comment_array as $comment): 
            $imagesrc = "image.php?id=".$comment["id"];
            ?>
                <article>  
                <div class="wrapper">
                <div class = "nameArea">
                    <span class="id"><?php echo $comment["id"]; ?></span>
                    <span>名前：</span>
                    <p class="username"><?php echo $comment["username"]; ?></p>
                    <time>:<?php echo $comment["postDate"]; ?></time>
                </div>
                <p class="comment"><?php echo $comment["comment"]; ?></p>
                 
                <?php
                if(!empty($comment["imageName"])):
                ?>
                    <img src= <?php echo '"'.$imagesrc.'"'?>, width="250">
                <?php endif;?>


                </div>
                </article>
            <?php endforeach; ?>
        </section>
        <form class="formWrapper" action="" enctype="multipart/form-data" method="POST">
        <!--actionの中身は空にするhttps://style.potepan.com/articles/20409.html#action82218221-->
            <div>
                <input type="submit" value="書き込む" name="submitButton">
                <label for="">名前：</label>
                <input type="text" name = "username">
            </div>
            <div>
                <textarea class="commentTextArea" name="comment"></textarea>
            </div>
            <div>
                ファイル：<br />
                <input type="file" name="upfile" size="30" /><br />
                <br />
            </div>
        </form>
    </div>
</body>
</html>