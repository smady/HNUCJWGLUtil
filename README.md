# HNUC 教务管理学生工具类

### 主要方法
| 方法 | 参数 | 返回值|
|:---|:----|:---|
|getSchedule()|无| array()|
|getGrades($term)|学期, 格式: 2015-2016-2| array()|
|getExamArrangement()|无| array()|


### Exception
| 错误码 | 异常所在方法 | 错误信息 |
|----:|:----|:---|
| -1 | __constuct() | cookies不具有登录状态 |
| -2 | fetch() | 网络连接错误 |
| -3 | login() | 登录失败 |


### 使用示例
1. 使用构造方法传入的cookie使用，得到课表
```php
<?php
require(__DIR__ . 'util.php');
$u = new Util('////your cookies');
$table = $u->getSchedule(); //得到课程表
var_dump($table);
```

2. 使用学号和密码登录后得到课表
```php
<?php
require(__DIR__ . 'util.php');
$u = new Util();
$u->login('你的学号', '你的密码');
$table = $u->getSchedule(); //得到课程表
var_dump($table);
```
