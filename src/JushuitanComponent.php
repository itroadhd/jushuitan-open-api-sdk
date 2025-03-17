<?php

namespace Jushuitan\OpenSDK;

use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\caching\CacheInterface;
use yii\di\Instance;
use yii\log\Logger;

class JushuitanComponent extends Component
{
    /** @var string */
    public $appKey;
    
    /** @var string */
    public $appSecret;
    
    /** @var array */
    public $clientConfig = [];
    
    /** @var string|CacheInterface */
    public $cache = 'cache';
    
    /** @var int */
    public $tokenCacheDuration = 7200;
    
    /** @var string */
    private $cacheKeyPrefix = 'jushuitan';
    
    /** @var Client */
    private $_client;
    
    /**
     * @throws InvalidConfigException
     */
    public function init()
    {
        parent::init();
        
        if (empty($this->appKey)) {
            throw new InvalidConfigException('AppKey must be set.');
        }
        
        if (empty($this->appSecret)) {
            throw new InvalidConfigException('AppSecret must be set.');
        }
        
        $this->cache = Instance::ensure($this->cache, CacheInterface::class);
    }
    
    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if ($this->_client === null) {
            $this->_client = new Client($this->appKey, $this->appSecret, $this->clientConfig);
            
            // 从缓存获取token
            $token = $this->cache->get($this->getCacheKey('access_token'));
            if ($token) {
                $this->_client->setAccessToken($token);
            }
        }
        
        return $this->_client;
    }
    
    /**
     * 获取访问令牌
     * @param string $code
     * @return array
     * @throws Exception\JushuitanException
     */
    public function getAccessToken(string $code): array
    {
        $result = $this->getClient()->getAccessToken($code);
        
        // 缓存token
        $this->cache->set(
            $this->getCacheKey('access_token'),
            $result['access_token'],
            $this->tokenCacheDuration
        );
        
        // 记录日志
        \Yii::getLogger()->log(
            "获取访问令牌成功: {$result['access_token']}",
            Logger::LEVEL_INFO,
            'jushuitan'
        );
        
        return $result;
    }
    
    /**
     * 刷新访问令牌
     * @param string $refreshToken
     * @return array
     * @throws Exception\JushuitanException
     */
    public function refreshToken(string $refreshToken): array
    {
        $result = $this->getClient()->refreshToken($refreshToken);
        
        // 缓存新token
        $this->cache->set(
            $this->getCacheKey('access_token'),
            $result['access_token'],
            $this->tokenCacheDuration
        );
        
        // 记录日志
        \Yii::getLogger()->log(
            "刷新访问令牌成功: {$result['access_token']}",
            Logger::LEVEL_INFO,
            'jushuitan'
        );
        
        return $result;
    }
    
    /**
     * 发送API请求
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return array
     * @throws Exception\JushuitanException
     */
    public function request(string $method, string $uri, array $params = []): array
    {
        // 记录请求日志
        \Yii::getLogger()->log(
            "API请求: {$method} {$uri} " . json_encode($params, JSON_UNESCAPED_UNICODE),
            Logger::LEVEL_INFO,
            'jushuitan'
        );
        
        $result = $this->getClient()->request($method, $uri, $params);
        
        // 记录响应日志
        \Yii::getLogger()->log(
            "API响应: " . json_encode($result, JSON_UNESCAPED_UNICODE),
            Logger::LEVEL_INFO,
            'jushuitan'
        );
        
        return $result;
    }
    
    /**
     * 生成缓存键
     * @param string $key
     * @return string
     */
    private function getCacheKey(string $key): string
    {
        return "{$this->cacheKeyPrefix}:{$this->appKey}:{$key}";
    }
}