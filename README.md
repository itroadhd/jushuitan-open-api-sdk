# 聚水潭开放平台 PHP SDK

这是聚水潭开放平台的PHP SDK，提供了简单易用的接口调用方式。

## 安装

通过Composer安装:

```bash
composer require jushuitan/open-api-sdk
```

## 基本使用

### Yii2框架中使用

1. 在配置文件中添加组件配置：

```php
'components' => [
    'jushuitan' => [
        'class' => 'Jushuitan\OpenSDK\JushuitanComponent',
        'appKey' => 'your-app-key',
        'appSecret' => 'your-app-secret',
    ],
]
```

2. 使用示例：

```php
// 获取访问令牌
$result = Yii::$app->jushuitan->getAccessToken('authorization-code');

// 刷新访问令牌
$result = Yii::$app->jushuitan->refreshToken('refresh-token');

// 发送API请求
$result = Yii::$app->jushuitan->request('GET', '/open/shops/query', [
    'page_no' => 1,
    'page_size' => 20,
]);
```

### 独立使用

```php
$client = new \Jushuitan\OpenSDK\Client('your-app-key', 'your-app-secret');

// 获取访问令牌
$result = $client->getAccessToken('authorization-code');

// 刷新访问令牌
$result = $client->refreshToken('refresh-token');

// 发送API请求
$result = $client->request('GET', '/open/shops/query', [
    'page_no' => 1,
    'page_size' => 20,
]);
```

## 版本历史

### v1.0.0 (2024-01-09)

- 首次发布稳定版本
- 实现基础的OAuth认证功能
- 支持API请求和签名生成
- 提供Yii2框架集成支持
- 完善的文档和使用示例

## 许可证

本项目采用MIT许可证，详情请参见[LICENSE](LICENSE)文件。