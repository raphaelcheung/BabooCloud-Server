# BabooCloud-Server

#### 介绍

BabooCloud 是基于 owncloud前端交互开发的私有云网盘。

前端引用了owncloud的交互和资源，后端在thinkphp6架构上进行全新设计，为大家提供最为精简的私有云盘功能。

服务器将会支持windows和linux，前端将会支持web、ios、android、pc客户端，项目才刚开始，开发量不小，欢迎同道人士参与

#### 软件架构

之前没有写web系统的经验，一边摸索一边写，先把功能实现了，以后再提升代码质量

1. 阅读代码前，你需要了解 [thinkphp6的架构](https://www.thinkphp.cn/)
1. 环境要求 php 7.4+，mysql 5.7+，服务器的运行环境目前只在win10下跑过，还没去linux验证
1. app\controller\ 控制器在此，处理外部请求
1. app\middleware\ 中间件处理免登录的身份验证，以及首次安装的跳转
1. app\lib\ 核心库在此
1. config\ 配置文件都在这，原来的thinkphp6不支持配置动态写入，修改了框架让其可以读写json配置

#### 已实现的功能点

1. 首次运行会进入安装页面，引导配置超级管理员账号、设置存储目录、数据库连接信息，要注意的是，数据库仅支持mysql/MariaDB
1. 安装过后会跳转到登录页面，登录一次可记住7天

#### 安装教程

1.  待添加

#### 使用说明

1.  待添加

#### 参与贡献

1.  Raphael Cheung，拥有多年的C++客户端、C#后端经验，现为项目的发起人和主要开发人


#### 引用申明

1.  AGPL，[owncloud](https://github.com/owncloud)：前端引用了owncloud的交互和资源并做了大量的修改
1.	Apache,[thinkphp6](https://www.thinkphp.cn/)：后端基于thinkphp6的框架进行全新设计
