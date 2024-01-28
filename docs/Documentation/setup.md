# Installation & Setup


## Prerequisites & Dependencies

Frigate requires PHP 8.1 or higher. It is recommended to use the latest stable version of PHP to ensure you have all the latest security patches and performance improvements.
Frigate depends on the following PHP extensions:

-  [mbstring](https://www.php.net/manual/en/book.mbstring.php) - Multibyte string functions (should be available everywhere)
-  [json](https://www.php.net/manual/en/book.json.php) - JavaScript Object Notation (should be available everywhere)

Beside core PHP extensions, Frigate also depends on the following PHP libraries, which are dependencies managed by Composer automatically:

- [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) Used for making HTTP requests.
- [sabre/uri](https://github.com/sabre-io/uri) Functions for making sense out of URIs.
- [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv) PHP dotenv loads environment variables from `.env` to `getenv()`, `$_ENV` and `$_SERVER` automagically.
- [twig/twig](https://github.com/twigphp/Twig) Twig, the flexible, fast, and secure template language for PHP.
- [firebase/php-jwt](https://github.com/firebase/php-jwt) A simple library to encode and decode JSON Web Tokens (JWT) in PHP.

!!! note
    Frigate aims to be lightweight and minimalistic. That's why we are doing our best to keep the number of dependencies as low as possible and only use the most essential and stable libraries.

## Installation

Install Frigate with composer:

```bash
composer require siktec/frigate
```

## Create a project

You may want to start with a bootstrap project to get started:

```bash
composer create-project siktec/frigate-bootstrap
```

## Project Structure

Frigate is designed to be simple and flexible. It does not enforce any specific project structure. You are free to organize your project however you want. However, we recommend the following project structure (which is also used in the Frigate Bootstrap project):

```bash
├── root
│   │
│   ├── api # API endpoints goes here (PSR-4)
│   ├── routes # Routes goes here (PSR-4)
│   ├── models # Models goes here (PSR-4)
│   ├── static # Static files goes here
│   ├── pages # Front-end stuff goes here (PSR-4)
│   ├── cli # CLI files goes here (PSR-4)
│   │
│   ├── index.php # Entry point
│   ├── .env # Environment variables goes here
│   ├── .htaccess # Apache configuration file (optional)
│   ├── composer.json # Composer configuration file
```



## Setting up the server

Frigate is designed to run on Apache and Nginx web servers. It is recommended to use the latest stable version of Apache or Nginx to ensure you have all the latest security patches and performance improvements.

No matter which web server you are using you will need to make sure that any requests to your Frigate application are sent to the `index.php` file. This is called "routing" and is configured differently depending on which web server you are using. For more information on configuring your web server, see the [official Apache](https://httpd.apache.org/docs/current/) or [Nginx](https://docs.nginx.com/nginx/admin-guide/web-server/web-server/) documentation.

### Apache

When using Apache, you need to make sure that the `mod_rewrite` [module](https://httpd.apache.org/docs/current/mod/mod_rewrite.html) is enabled. This can be done by running the following command:

```shell
# Enable mod_rewrite
sudo a2enmod rewrite

# Restart Apache to apply changes
sudo service apache2 restart
```

If you are using Apache, you can use the following `.htaccess` file in the root of your project to route all requests to the `index.php` file.
Besides routing, this `.htaccess` file also disables directory browsing and hides all environment files.

``` { .apacheconf .annotate }
# Disable directory browsing
Options -Indexes

# Hide all environment files
<Files .env>
    Order allow,deny
    Deny from all
</Files>

<IfModule mod_rewrite.c>
    
    RewriteEngine On 

    # allow HTTP basic authentication (1)
    RewriteCond %{HTTP:Authorization} ^(.+)$
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # RewriteRule to redirect all requests to index.php
    RewriteBase /
    RewriteRule . index.php [QSA,L]

</IfModule>
```

1. This is required if you are using HTTP Basic Authentication. Without this, the `Authorization` header will not be available to Frigate. Its mainly for Development and Testing purposes; so unless you are using HTTP Basic Authentication for production, comment out these lines.

!!! info
    If you created your project using the Frigate Bootstrap project, this `.htaccess` file is already included in your project.

Once you have created your `.htaccess` file, you need to make sure that your Apache virtual host is configured with the `AllowOverride` option. This will allow your `.htaccess` file to override the default settings of your Apache virtual host. You can do this by editing your virtual host configuration file and changing the `AllowOverride` option from `None` to `All`:

``` { .apacheconf .annotate }

sudo nano /etc/apache2/sites-available/000-default.conf

```

Inside the `<VirtualHost *:80>` block, change the `AllowOverride` option to `All`:

``` apacheconf hl_lines="4"
<VirtualHost *:80>
    <Directory /var/www/html>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    . . .
</VirtualHost>
```

Then restart Apache to apply changes:

```shell
sudo service apache2 restart
```

If everything is configured correctly, any requests to your Frigate application should now be routed to the `index.php` file.

??? note "Performance Considerations"
    `.htaccess` files and `mod_rewrite` are sometimes considered as a performance issue. When using `.htaccess` files every request to your application will be checked for the existence of a `.htaccess` file in every part of the directory tree. This means that several "additional" filesystem accesses are required for every request.

    In our case, we are using a single `.htaccess` file in the root of our project. The rule will terminate immediately for any request `[QSA,L]`. This should minimize the performance impact of using `.htaccess` files. However, if you are concerned about performance, you can disable `.htaccess` files and move the rules to your virtual host configuration file. This will reduce the number of filesystem accesses required for every request. see the [mod_rewrite](https://httpd.apache.org/docs/current/rewrite/) documentation for more information.

    The reason we are using `.htaccess` is that most shared hosting providers do not allow you to edit the virtual host configuration file. `.htaccess` files are the only way to configure your application in this case. It is also the easiest way to configure your application and add additional rules that may be required for your application to work correctly.

### Nginx

When using Nginx, you need to make sure that the `try_files` [directive](https://nginx.org/en/docs/http/ngx_http_core_module.html#try_files) is configured correctly. This can be done by editing your Nginx configuration file, the goal is to make sure that any requests to your Frigate application are routed to the `index.php` file.

Nginx [does not support](https://www.nginx.com/resources/wiki/start/topics/examples/likeapache-htaccess/) `.htaccess` files, so you need to make sure that your Nginx configuration file is configured correctly. If you are using Nginx, you need to adjust the configuration file to include the following `location` block, This will route all requests to the `index.php`:

```shell hl_lines="11-19"
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    root /var/www/html;

    index index.php index.html index.htm index.nginx-debian.html;

    server_name example.com www.example.com;

    # Block access to environment files (just in case)
    location ~ /\.env {
        deny all;
    }

    # Route all requests to index.php even if its a static file or a directory or the file does not exist:
    location / {
        try_files /index.php?$query_string;
    }


    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
    }

    # deny access to .htaccess, .htpasswd.
    location ~ /\.ht {
        deny all;
    }
}

``` 

!!! tip "Nginx configuration file location"
    The configuration file by default is located at `/etc/nginx/sites-available/default` or `/etc/nginx/nginx.conf` depending on your setup.