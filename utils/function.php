<?php

$filePostDate = date("Y-m-d_H-i-s_"); //同名の画像ファイルがアップロードされた際の区別用＆エラーログファイルの区別用

//XSS対策用のエスケープ関数
function myEscape($str) {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, "UTF-8");
}

//エラーログ作成関数
function myErrorLog($str) {
    global $filePostDate;
    $myErrorLogContents = $filePostDate. $str."\n";
    return error_log($myErrorLogContents, 3, "./error.log");    
}

?>
