Rss
==========

Rss是一个方便扩展Rss订阅源的api项目。目前收集了知乎，更多订阅源尽请期待。

[![Minimum PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.0-brightgreen.svg)](https://php.net/)
[![Stable](https://img.shields.io/badge/Rss-Stable-brightgreen.svg)](https://github.com/nameNotNull/mp_rss)



兄弟项目： [小程序Rss](https://github.com/nameNotNull/mp_rss)

Requirements
------------
 - PHP >= 7.1
 - yaf >= 3.0

特性
------------
* 支持xml转化为json格式的response
* 工厂模式方便扩展不同Rss来源
* 支持cache
* api中文文档：
    * [Rss订阅](https://namenotnull.github.io/blog/)
* 小程序中文文档：
    * [小程序Rss订阅](https://namenotnull.github.io/blog/)

# 最简配置

## cache 配置

> 配置config/cache.ini配置文件,并开启本地redis服务
```
[product]
redis.default.enable = On
redis.default.write.enable = On
redis.default.write.host = "127.0.0.1"
redis.default.write.port = "6379"
redis.default.write.database = "0"
redis.default.write.timeout = "1"
redis.default.read.enable = On
redis.default.read.host = "127.0.0.1"
redis.default.read.port = "6379"
redis.default.read.database = "0"
redis.default.read.timeout = "1"

[product-aws]


[develop:product]


[test]


[test-auto:test]

[preline]


```

## api nginx配置
```
  server {
       listen 80;
       server_name rss.dev.com;
       root /data/www/develop/rss/htdocs;
       access_log /data/logs/rss.dev.com-error_log  main;
       error_log  /data/logs/rss.dev.com-error_log;
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
http:///rss/search.json?source=zhihu&type=hotlist
```



Contact
-----------------

* Please feel free to [raise issue](https://github.com/nameNotNull/rss/issues), or give us some advice. :)



License
-----------------

* Apache License, Version 2.0
