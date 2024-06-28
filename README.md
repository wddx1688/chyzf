# PHP 7.4, MySQL 5.7 and Nginx with Docker

If you need to setup a PHP-MYSQL-NGINX environment in your machine, you only have to clone this repositorio and run:

~~~sh
docker-compose up -d
~~~

Then just go to http://localhost:8780/ for the "Hello World" message :).

## The repository structure

### The "app" folder

The `app` folder will contain all the code of your site. In this folder `nginx` its going to search for the `index.php`.

### The "volumes" folder

This folder constains all the info of the containers that we need to touch or backup

#### volumes > mysql

Contains `my.cnf` so you can config your mysql and a `data` folder points to `/var/lib/mysql`. This allows you to keep your data after deleting the containers.

#### volumes > nginx

Contains `app.conf` with your server nginx configuration

#### volumes > php

Contains `your_site.ini` (you can change the name) to add php configuration
