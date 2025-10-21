# PHP 8.2 を使う
FROM php:8.2-apache

# 作業ディレクトリ
WORKDIR /var/www/html

# ファイルをコンテナにコピー
COPY . /var/www/html

# 必要なら拡張モジュールを追加（例: mysqli）
RUN docker-php-ext-install mysqli pdo pdo_mysql

# ApacheをRender向けに設定（リッスンポートを変更）
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf

# 公開ポート
EXPOSE 10000

# 起動コマンド
CMD ["apache2-foreground"]
