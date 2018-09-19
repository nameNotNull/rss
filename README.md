Initial Project
==========


[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.1-8892BF.svg?style=flat-square)](https://php.net/)
[![Build Status](https://img.shields.io/travis/sebastianbergmann/phpunit/master.svg?style=flat-square)](https://phpunit.de/build-status.html)

[npm-image]: https://img.shields.io/npm/v/anyproxy.svg?style=flat-square
[npm-url]: https://npmjs.org/package/anyproxy
[node-url]: http://nodejs.org/download/
[download-image]: https://img.shields.io/npm/dm/anyproxy.svg?style=flat-square
[download-url]: https://npmjs.org/package/anyproxy

Initial Project 是基于yaf的一个php框架，集成了illuminate/database、illuminate/validation、monolog/monolog等第三方库，并且可以使用composer灵活扩展的一个项目初始demo。此外，本项目还支持swagger自动生成api文档。

> 本脚手架实际是综合api、swagger文档两个站点的集成。nginx需配置两个host:api和swagger文档。

Requirements
------------
 - PHP >= 7.1
 - yaf >= 3.0

特性
------------
* 实现了mysql的model层开发，集成eloquent的ORM
* 支持输入参数的验证
* 支持各级别日志
* 集成单元测试demo
* 集成swagger的ui站点及自动生成
* 中文文档：
    * [yaf的项目脚手架](https://namenotnull.github.io/blog/)

# 最简配置

## db 配置

> 按照config/database.ini配置文件，自行更改host等配置创建 test数据库的test表

```
CREATE TABLE `test` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '自增id',
  `name` varchar(20) NOT NULL DEFAULT '' COMMENT '用户名',
  `user_id` bigint(20) NOT NULL COMMENT '用户id',
  `mobile` varchar(11) NOT NULL DEFAULT '',
  `address` varchar(20) NOT NULL DEFAULT '' COMMENT '住址',
  `status` tinyint(3) unsigned NOT NULL COMMENT '状态',
  `create_time` datetime DEFAULT NULL,
  `modify_time` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4
```

## api nginx配置
```
 server {
     listen 80;
     server_name initial.project.dev.com;
     root /data/www/develop/initial_project/htdocs;
     access_log /data/logs/initial.project.dev.com-error_log  main;
     error_log  /data/logs/initial.project.dev.com-error_log;
     add_header Access-Control-Allow-Origin *;
     add_header Access-Control-Allow-Methods 'GET, POST, OPTIONS';

     add_header Engine 'PHP';

     rewrite "^/(.*)" /index.php/$1 last;

     location  / {
         include tengine.fastcgi_params;
         fastcgi_pass 127.0.0.1:9000;
         fastcgi_param  REQUEST_URI  $request_uri;
     }

 }

```


## fastcgi_params配置
```
 fastcgi_split_path_info ^(.+?\.php)(.*)$;
 fastcgi_index index.php;

 fastcgi_param  QUERY_STRING       $query_string;
 fastcgi_param  REQUEST_METHOD     $request_method;
 fastcgi_param  CONTENT_TYPE       $content_type;
 fastcgi_param  CONTENT_LENGTH     $content_length;

 fastcgi_param  SCRIPT_NAME        $fastcgi_script_name;
 fastcgi_param  REQUEST_URI        $request_uri;
 fastcgi_param  DOCUMENT_URI       $document_uri;
 fastcgi_param  DOCUMENT_ROOT      $document_root;
 fastcgi_param  SERVER_PROTOCOL    $server_protocol;
 fastcgi_param  HTTPS              $https if_not_empty;

 fastcgi_param  GATEWAY_INTERFACE  CGI/1.1;
 fastcgi_param  SERVER_SOFTWARE    nginx/$nginx_version;

 fastcgi_param  REMOTE_ADDR        $remote_addr;
 fastcgi_param  REMOTE_PORT        $remote_port;
 fastcgi_param  SERVER_ADDR        $server_addr;
 fastcgi_param  SERVER_PORT        $server_port;
 fastcgi_param  SERVER_NAME        $server_name;
 fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
 fastcgi_param  PATH_INFO        $fastcgi_path_info;

     # PHP only, required if PHP was built with --enable-force-cgi-redirect
 fastcgi_param  REDIRECT_STATUS    200;
```
> 重启nginx  systemctrl restart tengine.service


一个合规的http请求:
```
http://initial.project.dev.com/test/show.json?user_id=123
```





# 使用swagger模块


## swagger 文档站点nginx配置
```
 server {
     listen 80;
     server_name swagger.dev.com;
     root /data/www/develop/initial_project/swagger-ui/dist;
     access_log /data/logs/swagger.dev.com-error_log  main;
     error_log  /data/logs/swagger.dev.com-error_log;

     add_header Engine 'PHP';
     location / {
         index index.html index.htm;
     }
 }
```

## 修改swagger配置

> 指定 swagger测试的host

```
 *   @OA\Server(
 *     url="http://initial.project.dev.com/",
 *     description="在线测试Host",
 *   ),
```

## openapi配置文件生成

> 执行下面脚本，会在swagger-ui/dist目录下生成一个openapi.json文件，可以将命令加入到crontab里，自动执行文档生成


```
php /data/www/develop/initial_project/htdocs/console.php request_uri="/console/swagger/create/parameter"

```

## 指定swager的文档显示json文件

> 修改 swagger-ui/dist 目录下 index.html 指定的json文件为上一步生成的openapi.json文件

![https://github.com/nameNotNull/initial_project/blob/master/resources/swagger_index.png](https://github.com/nameNotNull/initial_project/blob/master/resource/swagger_index.png)

![https://github.com/nameNotNull/initial_project/blob/master/resources/swagger.png](https://github.com/nameNotNull/initial_project/blob/master/resource/swagger.png)





# 使用集成的单元测试模块
```
php ./vendor/bin/phpunit tests/test.php 
```
>执行结果如下

![https://github.com/nameNotNull/initial_project/blob/master/resources/phpunit.png](https://github.com/nameNotNull/initial_project/blob/master/resource/phpunit.png)



Contact
-----------------

* Please feel free to [raise issue](https://github.com/nameNotNull/initial_project/issues), or give us some advice. :)



License
-----------------

* Apache License, Version 2.0
