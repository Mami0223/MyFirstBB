# MyFirstBB
PHPとMySQLで掲示板作成

# Description
シンプルな画像掲示板です。

AWS EC2にホストしています。
下記リンクから掲示板を閲覧可能です。（※認証が必要です）
http://54.178.114.228/MyFirstBB/MyFirstBB.php

投稿するためには、名前とコメントを入力し、必要な場合は「ファイルを選択」を押して画像を添付して、「書き込む」ボタンを押してください。

# Features
- ベーシック認証
- 画像・テキスト投稿
- 入力のエラー処理<br>
　　・ 名前とコメント内容は空欄不可(スペースのみの入力も不可)<br>
　　・ 投稿ファイルサイズの拡張子はpng,jpg,jpeg,gif,bmpのみ<br>
　　・ 投稿ファイルサイズは5MBまで<br>
- ページネーション
- SQLインジェクション対策
- クロスサイトスクリプティング対策
- レスポンシブデザイン

# Requirements
- Ubuntu 22.04.1
- PHP 8.1.2
- MySQL 8.0.31
