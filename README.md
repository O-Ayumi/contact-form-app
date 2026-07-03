# COACHTECH お問い合わせフォーム
## 概要
Web上からのお問い合わせを受け付け、管理ユーザーが内容を確認・管理できるお問い合わせフォームアプリです。
管理ユーザーは、問い合わせに紐づくカテゴリーやタグを編集し、問い合わせ内容を管理しやすくすることを目的としています。

## 実装した機能
- 実装中

## ER図
<img width="842" height="595" alt="ER-diagram" src="https://github.com/user-attachments/assets/1a5136dc-dc95-4860-a768-ce4ce4f2be12" />


## 環境構築手順
### 前提
- Docker/Docker Desktopがインストール済み
- Gitがインストール済み

### 手順
1. リポジトリをクローンする
git clone https://github.com/O-Ayumi/contact-form-app.git
cd contact-form-app

2. Laravel Sailを利用してコンテナを起動
./vendor/bin/sail up -d
(エイリアスを設定していればsail up -d)

4. .envファイルのデータベース接続情報などを環境に合わせて変更する
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
になっているのを確認する

5. アプリケーションキーを生成する
sail artisan key:generate

6. データベースのマイグレーションと初期データ投入
sail artisan migrate --seed

7. フロントエンドビルド(Tailwind CSS)
sail npm install
sail npm run dev(別のターミナルで起動)

8. ブラウザから http://localhost にアクセスし、お問い合わせフォーム画面が表示されることを確認


## 使用技術
- Laravel 10
- MySQL 8.0
- Nginx
- Docker
- phpMyAdmin
- PHP
- Tailwind CSS

## APIエンドポイント一覧
- 実装中

## 開発環境URL
http://localhost

## 作成者
緒方あゆみ






