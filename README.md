# COACHTECH お問い合わせフォーム
## 概要
Web上からのお問い合わせを受け付け、管理ユーザーが内容を確認・管理できるお問い合わせフォームアプリです。
管理ユーザーは、問い合わせに紐づくカテゴリーやタグを編集し、問い合わせ内容を管理しやすくすることを目的としています。

## 実装した機能
### お問い合わせフォーム
- お問い合わせ項目の入力・送信機能
- 各項目のバリデーションチェック(名前、性別、メールアドレス、電話番号など)
- 入力に誤りがあった際のエラーメッセージ表示
- お問い合わせデータのデータベースへの保存

### 管理画面
- 認証ユーザー登録時の項目の入力・送信機能
- 各項目のバリデーションチェック(メールアドレスの重複チェックなど)
- 認証ユーザー(管理ユーザー)のみアクセス可能、未認証時はログイン画面へ遷移
- 登録されたお問い合わせデータのページネーション付き一覧表示
- 条件設定(キーワード、性別、カテゴリ、日付)でお問い合わせデータの検索ができる機能
- 各お問い合わせの詳細情報表示
- お問い合わせレコードの削除機能
- タグの管理機能(新規作成、編集、更新、削除)
- CSVエクスポートボタンでお問い合わせ内容をBOM付CSVとしてダウンロード(検索条件指定時は一致するデータのみ)
- API認証でのお問い合わせの一覧表示・詳細表示・更新・削除機能、お問い合わせの新規登録

### テスト・品質管理
- 各機能テスト(feature Test)実装
- お問い合わせ入力フォームの登録・送信において正常系・異常系のテスト
- お問い合わせ一覧画面の検索・詳細・更新・削除の正常系・異常系のテスト
- タグの表示・新規作成・編集・削除の正常系・異常系のテスト
- APIでのお問い合わせ一覧・検索・作成・詳細・更新・削除の正常系・異常系のテスト
- `sail artisan test --coverage`にてテストカバレッジ88.1％を達成

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

3. .envファイルのデータベース接続情報などを環境に合わせて変更する
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password
になっているのを確認する

4. Dockerコンテナを起動する
./vendor/bin/sail up -d

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
お問い合わせ一覧
- Method: GET
- Path: /api/v1/contacts

お問い合わせ詳細
- Method: GET
- Path: /api/v1/contacts/{contact}

お問い合わせ新規作成
- Method: POST
- Path: /api/v1/contacts

お問い合わせ更新
- Method: PUT
- Path: /api/v1contacts/{contact}

お問い合わせ削除
- Method: DELETE
- Path: /api/v1/contacts/{contact}

## 開発環境URL
http://localhost

## 作成者
緒方あゆみ


