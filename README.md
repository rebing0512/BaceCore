# Helper
[use MBCore\MCore\Libraries\Helper]
1. getPlatform(Request $request) 获取当前的平台类型，依据：server('HTTP_USER_AGENT'))
2. safeEncode($data) 安全base64_encode将可能会被浏览器破坏的符号替换成其他符号
3. safeDecode($data) 安全解码 对应 safeEncode
4. Encrypt($data, $key) 简单加密
5. Decrypt($data,$key)  简单解密

# 数据库 migrate 验证机制
php artisan mbcore:updatedb

> 需要在 /config/database.php 中增加
> 'mysql_test' => config("mbcore_mcore.mysql_test",[]), //增加测试数据库

# 配置文件说明
1. 使用mbcore_mcore.mysql_test的配置
2. 加载.env的专有配置 _TEST
3. 采用.env的正式服务器配置，并将数据库名修改成后缀为 _test 的数据库
