<?php

namespace Jushuitan\OpenSDK;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Jushuitan\OpenSDK\Exception\JushuitanException;
use Psr\Http\Message\ResponseInterface;

class Client
{
    private const API_HOST = 'https://openapi.jushuitan.com';
    private const TOKEN_URL = '/auth/token';
    
    /** @var string */
    private $appKey;
    
    /** @var string */
    private $appSecret;
    
    /** @var string|null */
    private $accessToken;
    
    /** @var HttpClient */
    private $httpClient;
    
    /** @var array */
    private $config;
    
    public function __construct(string $appKey, string $appSecret, array $config = [])
    {
        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $this->config = array_merge([
            'timeout' => 30,
            'base_uri' => self::API_HOST,
        ], $config);
        
        $this->httpClient = new HttpClient($this->config);
    }
    
    /**
     * 获取访问令牌
     * @param string $code 授权码
     * @return array
     * @throws JushuitanException
     */
    public function getAccessToken(string $code): array
    {
        try {
            $response = $this->httpClient->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'app_key' => $this->appKey,
                    'app_secret' => $this->appSecret,
                ],
            ]);
            
            $result = $this->handleResponse($response);
            $this->accessToken = $result['access_token'];
            return $result;
        } catch (GuzzleException $e) {
            throw new JushuitanException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * 刷新访问令牌
     * @param string $refreshToken
     * @return array
     * @throws JushuitanException
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $response = $this->httpClient->post(self::TOKEN_URL, [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'app_key' => $this->appKey,
                    'app_secret' => $this->appSecret,
                ],
            ]);
            
            $result = $this->handleResponse($response);
            $this->accessToken = $result['access_token'];
            return $result;
        } catch (GuzzleException $e) {
            throw new JushuitanException($e->getMessage(), $e->getCode(), $e);
        }
    }
    
    /**
     * 发送API请求
     * @param string $method 请求方法
     * @param string $uri 请求路径
     * @param array $params 请求参数
     * @return array
     * @throws JushuitanException
     */
    private function generateSign(array $params): string
    {
        $stringSign = $this->appSecret;
        ksort($params);
        
        foreach ($params as $key => $value) {
            $stringSign .= $key . $value;
        }
        
        return md5($stringSign);
    }

    public function request(string $method, string $uri, array $params = []): array
    {
        if (empty($this->accessToken)) {
            throw new JushuitanException('Access token is not set');
        }
        
        $requestParams = [
            'app_key' => $this->appKey,
            'access_token' => $this->accessToken,
            'timestamp' => time(),
            'version' => '2',
            'charset' => 'utf-8',
            'biz' => json_encode($params)
        ];
        
        $requestParams['sign'] = $this->generateSign($requestParams);
        
        try {
            $response = $this->httpClient->request($method, $uri, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'
                ],
                'form_params' => $requestParams
            ]);
            
            return $this->handleResponse($response);
        } catch (GuzzleException $e) {
            throw new JushuitanException($e->getMessage(), $e->getCode(), $e);
        }catch (\Exception $e) {
            throw new JushuitanException($e->getMessage(), $e->getCode(), $e);
        }
        return [];
    }
    
    /**
     * 处理API响应
     * @param ResponseInterface $response
     * @return array
     * @throws JushuitanException
     */
    private function handleResponse(ResponseInterface $response): array
    {
        $body = (string) $response->getBody()->getContents();;
        $data = json_decode($body, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new JushuitanException('Failed to parse response: ' . json_last_error_msg());
        }
        
        if (isset($data['code']) && $data['code'] !== 0) {
            throw new JushuitanException(
                $data['msg'] ?? 'Unknown error',
                $data['code'] ?? 0
            );
        }
        
        return $data;
    }
    
    /**
     * 设置访问令牌
     * @param string $accessToken
     * @return void
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }
    
    /**
     * 获取当前访问令牌
     * @return string|null
     */
    public function getToken(): ?string
    {
        return $this->accessToken;
    }
}