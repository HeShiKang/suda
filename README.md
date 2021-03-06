#  苏打PHP框架

该框架是学习网站开发后的产物，作为一名业余的开发人员，造轮子对我们来说是一个很好的学习技巧的方式，我们时间
充足不是么？现行的PHP框架用的不是很开心就是。所以有了这个框架，按照自己的思路，想法去开发网站，感觉不是一般的
开心。框架的模式基本应用的MVC的思路，但是却不被认为是MVC~拥有强大的路由规则和完善的网页模板生成器，可以很容易的开发出现有的网站出来，使用本框架 **你不需要过度关心网站安全问题，自带XSS过滤和MySQL过滤**

> 一切不以学习为目的造轮子就是浪费时间~~

## 框架主要实现的功能

- [x] 应用模块机制
- [x] 页面路由
- [x] 数据操作辅助类
- [x] Debug工具
- [x] 控制台自动构建功能
- [x] 事件监听器
- [x] 简易PHP模板
- [x] 日志记录工具
- [x] 网页控制器
- [x] DAO类
- [ ] DocMe工具




## 快速开始使用
1. 下载框架

  ```
  git clone https://github.com/DXkite/suda 
  ```
  并复制public文件夹的路径

2. 设置网站根目录     
  把服务器的网站根目录调整为刚刚下载的框架的 public 目录，把public目录作为网站的根目录。

**2.1 LINUX 用户注意** 在Linux上面安装的时候需要设置好权限组,来保证开发者和服务器都享有对应用的操作权限
```
sudo usermod -aG 服务器组名 开发者用户名
sudo chmod g+rw 应用目录（APP_DIR目录）
```
如：服务器设置的 Group ID 为：daemon 用户ID为：dxkite
则命令为:
```
sudo usermod -aG daemon dxkite
```

3. 访问网站

## 文档
[文档参考](docs/readme.md)
[路由使用](docs/tools/router.md)


## 历史版本 Or Demo

- [DxSite](https://github.com/DXkite/DxSite)   
- [ATD_MINI](https://github.com/DXkite/atd_mini)   
- [ATD3CN](https://github.com/DXkite/atd3.cn)   

----------------
