# Time Card App

勤怠管理システム - Laravel 10.x + Docker環境で構築される出退勤管理アプリケーション

## � プロジェクト概要

Time Card Appは、企業の勤怠管理を効率化するWebアプリケーションです。
一般スタッフは日々の出退勤や休憩時間を打刻でき、勤怠データの確認や修正申請が可能です。
管理者は全スタッフの勤怠データを一元管理し、修正申請の承認やCSVエクスポートが行えます。

### 主な特徴
- 📱 **シンプルな打刻システム**: 出勤・休憩開始・休憩終了・退勤を簡単操作
- 📊 **月次勤怠管理**: カレンダー形式で勤怠データを視覚的に確認
- ✏️ **修正申請機能**: 打刻ミスの修正申請と承認フロー
- 👥 **スタッフ別管理**: 管理者は個別スタッフの勤怠データを詳細確認
- 📧 **メール認証**: セキュアな新規登録プロセス
- 💾 **CSV出力**: 月次勤怠データのエクスポート機能

## 📋 目次
- [プロジェクト概要](#プロジェクト概要)
- [環境構築](#環境構築)
- [使用技術](#使用技術)
- [機能一覧](#機能一覧)
- [データベース設計](#データベース設計)
- [テーブル仕様書](#テーブル仕様書)
- [ログイン情報](#ログイン情報)

---

## 🚀 環境構築

### 必要な技術
- Docker
- Docker Compose

### セットアップ手順

```bash
# 1. プロジェクトをクローン
git clone https://github.com/taotomo/time-card-app.git
cd time-card-app

# 2. Docker環境の構築と起動
docker-compose up -d --build

# 3. Composer依存関係のインストール
docker-compose exec php composer install

# 4. 環境設定ファイルのコピー
cp src/.env.example src/.env

# 5. アプリケーションキーの生成
docker-compose exec php php artisan key:generate

# 6. データベースマイグレーション & ダミーデータ投入
docker-compose exec php php artisan migrate:fresh --seed --seeder=DummyDataSeeder

# 7. ブラウザでアクセス
# http://localhost
```

---

## 🛠 使用技術

| カテゴリ | 技術 |
|---------|------|
| バックエンド | Laravel 10.x |
| 認証 | Laravel Fortify |
| データベース | MySQL 8.0 |
| Webサーバー | Nginx |
| コンテナ | Docker & Docker Compose |
| メール確認 | MailHog |
| フロントエンド | Blade Templates |

---

## ✨ 機能一覧

### 一般ユーザー（スタッフ）
- ✅ ユーザー登録・ログイン（メール認証必須）
- ✅ 出退勤打刻（出勤・退勤・休憩開始・休憩終了）
- ✅ 勤怠一覧表示（月次カレンダー形式）
- ✅ 勤怠詳細・修正申請
- ✅ 修正申請一覧（承認待ち・承認済み）

### 管理者
- ✅ ログイン
- ✅ 日付別勤怠一覧表示
- ✅ スタッフ別勤怠詳細・月次勤怠
- ✅ スタッフ一覧表示
- ✅ 修正申請一覧（承認待ち・承認済み）
- ✅ 修正申請承認機能
- ✅ CSV出力（スタッフ別月次勤怠）

---

## 📊 データベース設計

### ER図

```
┌─────────────────────────┐
│       users             │
├─────────────────────────┤
│ id (PK)                 │
│ name                    │
│ email (UNIQUE)          │
│ email_verified_at       │
│ password                │
│ remember_token          │
│ created_at              │
│ updated_at              │
└─────────────────────────┘
            │
            │ 1
            │
            │ *
            ▼
┌─────────────────────────┐
│     attendances         │
├─────────────────────────┤
│ id (PK)                 │
│ user_id (FK)            │────┐
│ clock_in                │    │
│ clock_out               │    │
│ break_times (JSON)      │    │
│ remarks                 │    │
│ approval_status         │    │
│ created_at              │    │
│ updated_at              │    │
└─────────────────────────┘    │
                               │
                               │
                               └─→ users.id
```

### リレーション
- **users 1 : N attendances**
  - 1人のユーザーは複数の勤怠記録を持つ
  - 1つの勤怠記録は1人のユーザーに紐づく

---

## 📝 テーブル仕様書

### 1. usersテーブル（ユーザー情報）

| カラム名 | 型 | PRIMARY KEY | UNIQUE KEY | NOT NULL | FOREIGN KEY | 説明 |
|---------|-----|-------------|------------|----------|-------------|------|
| id | unsigned bigint | ○ | - | ○ | - | ユーザーID |
| name | varchar(255) | - | - | ○ | - | ユーザー名 |
| email | varchar(255) | - | ○ | ○ | - | メールアドレス |
| email_verified_at | timestamp | - | - | - | - | メール認証日時 |
| password | varchar(255) | - | - | ○ | - | パスワード（ハッシュ化） |
| remember_token | varchar(100) | - | - | - | - | ログイン保持トークン |
| created_at | timestamp | - | - | - | - | 作成日時 |
| updated_at | timestamp | - | - | - | - | 更新日時 |

**備考:**
- 管理者と一般ユーザーを同じテーブルで管理
- 管理者判定: `email = 'admin@example.com'`

---

### 2. attendancesテーブル（勤怠記録）

| カラム名 | 型 | PRIMARY KEY | UNIQUE KEY | NOT NULL | FOREIGN KEY | 説明 |
|---------|-----|-------------|------------|----------|-------------|------|
| id | unsigned bigint | ○ | - | ○ | - | 勤怠ID |
| user_id | unsigned bigint | - | - | ○ | users(id) | ユーザーID |
| clock_in | datetime | - | - | ○ | - | 出勤時刻 |
| clock_out | datetime | - | - | - | - | 退勤時刻 |
| break_times | json | - | - | - | - | 休憩時間（複数対応）<br>例: `[{"start":"12:00","end":"13:00"}]` |
| remarks | text | - | - | - | - | 備考（修正理由など） |
| approval_status | tinyint | - | - | ○ | - | 承認ステータス<br>0: 通常<br>1: 承認待ち<br>2: 承認済み |
| created_at | timestamp | - | - | - | - | 作成日時 |
| updated_at | timestamp | - | - | - | - | 更新日時 |

**備考:**
- `user_id` は外部キー制約あり（`ON DELETE CASCADE`）
- `break_times` はJSON形式で複数の休憩時間を保存可能
- `approval_status` により修正申請の状態を管理

---

## 🔑 ログイン情報

### 管理者
- **名前**: 管理者
- **メールアドレス**: `admin@example.com`
- **パスワード**: `password`

### 一般ユーザー
| 名前 | メールアドレス | パスワード |
|------|--------------|-----------|
| 山田太郎 | yamada@example.com | password |
| 佐藤花子 | sato@example.com | password |
| 鈴木一郎 | suzuki@example.com | password |
| 田中美咲 | tanaka@example.com | password |
| 高橋健太 | takahashi@example.com | password |

**※ すべてメール認証済みですぐにログインできます**

---

## 📁 ディレクトリ構成

```
time-card-app/
├── docker/                    # Docker設定
│   ├── nginx/
│   └── php/
├── src/                       # Laravelアプリケーション
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/
│   │   │   │   ├── AttendanceController.php  # 管理者用勤怠管理
│   │   │   │   └── StaffController.php       # 一般ユーザー用
│   │   │   └── Requests/
│   │   └── Models/
│   │       ├── User.php
│   │       └── Attendance.php
│   ├── database/
│   │   ├── migrations/
│   │   └── seeders/
│   ├── public/
│   │   └── css/              # スタイルシート
│   ├── resources/
│   │   └── views/
│   │       ├── layouts/      # レイアウトテンプレート
│   │       ├── staff/        # 一般ユーザー画面
│   │       ├── attendance/   # 管理者画面
│   │       └── components/   # 共通コンポーネント
│   └── routes/
│       └── web.php
├── docker-compose.yml
└── README.md
```

---

## 🌐 URL一覧

### 一般ユーザー
- ログイン: `http://localhost/login`
- 勤怠打刻: `http://localhost/attendance`
- 勤怠一覧: `http://localhost/attendance/list`
- 申請一覧: `http://localhost/requests`

### 管理者
- ログイン: `http://localhost/admin/login`
- 勤怠一覧: `http://localhost/admin/attendance/list`
- スタッフ一覧: `http://localhost/admin/staff/list`
- 申請一覧: `http://localhost/admin/requests`

### その他
- MailHog（メール確認）: `http://localhost:8025`
- phpMyAdmin: `http://localhost:8080`

---

## 📧 メール認証について

一般ユーザーの新規登録後、メール認証が必要です：
1. 新規登録完了後、認証メール送信画面が表示されます
2. MailHog（`http://localhost:8025`）にアクセス
3. 受信したメールから認証リンクをクリック
4. 認証完了後、勤怠打刻画面にリダイレクトされます

---

## 🔄 データリセット

```bash
# テーブルとダミーデータをリセット
docker-compose exec php php artisan migrate:fresh --seed
```

---

## 📚 参考資料

- [Laravel 10.x ドキュメント](https://laravel.com/docs/10.x)
- [Laravel Fortify](https://laravel.com/docs/10.x/fortify)
- [Docker公式ドキュメント](https://docs.docker.com/)
