<?php

date_default_timezone_set("Asia/Tokyo");

$comment_array = array();
$pdo = null;
$stmt = null;
$error_messages = array();

//DB接続
try{
    $pdo = new PDO('mysql:host=localhost;dbname=bb-database', "root", "chinzanso1215");
}catch(PDOException $e){
    echo $e->getMessage();
}


//フォームを打ち込んだとき
if(!empty($_POST["submitButton"])){

    //名前のチェック
    if(empty($_POST["username"])){
        echo "名前を入力してください";
        $error_messages["username"] = "名前を入力してください";
    }
    //コメントのチェック
    if(empty($_POST["comment"])){
        echo "コメントを入力してください";
        $error_messages["comment"] = "コメントを入力してください";
    }

    //エラーメッセージが何もない時だけデータ保存できる
    if (empty($error_messages)) {
        $postDate = date("Y-m-d H:i:s");

        try {
            $stmt = $pdo->prepare("INSERT INTO `bb-table` (`username`, `comment`, `postDate`) VALUES (:username, :comment, :postDate);");
            $stmt->bindParam(':username', $_POST['username'], PDO::PARAM_STR);
            $stmt->bindParam(':comment', $_POST["comment"], PDO::PARAM_STR);
            $stmt->bindParam(':postDate', $postDate, PDO::PARAM_STR);

            $stmt->execute();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
}


//DBからコメントデータを取得する
$sql = "SELECT username,comment,postDate FROM `bb-table` ";
$comment_array = $pdo->query($sql);

//DBの接続を閉じる
$pdo = null;


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
    <h1 class="title">PHPで掲示板アプリ</h1>
    <hr>
    <div class="boardWrapper">
        <section>
            <?php foreach($comment_array as $comment): ?>
                <article>  
                <div class="wrapper">
                <div class = "nameArea">
                    <span>名前：</span>
                    <p class="username"><?php echo $comment["username"]; ?></p>
                    <time>:<?php echo $comment["postDate"]; ?></time>
                </div>
                <p class="comment"><?php echo $comment["comment"]; ?></p>
                </div>
                </article>
            <?php endforeach; ?>
        </section>
        <form class="formWrapper" method="POST">
            <div>
                <input type="submit" value="書き込む" name="submitButton">
                <label for="">名前：</label>
                <input type="text" name = "username">
            </div>
            <div>
                <textarea class="commentTextArea" name="comment"></textarea>
            </div>
        </form>
    </div>
</body>
</html>