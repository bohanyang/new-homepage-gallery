<?php

namespace App\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Monolog\Handler\Curl;
use Symfony\Contracts\Cache\ItemInterface;

class FeishuBotHandler extends AbstractProcessingHandler
{
    private $appId;
    private $appSecret;
    private $channel;
    /** @var CacheInterface */
    private $cache;
    private $token;

    public function __construct(
        string $appId,
        string $appSecret,
        array $channel,
        CacheInterface $cache,
        $level = Logger::DEBUG,
        bool $bubble = true
    )
    {
        parent::__construct($level, $bubble);

        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->channel = $channel;
        $this->cache = $cache;
        $this->level = $level;
        $this->bubble = $bubble;
    }

    /**
     * @inheritDoc
     */
    protected function write(array $record) : void
    {
        $this->send($record['formatted']);
    }

    protected function send(string $message) : void
    {
        $ch = curl_init();
        $url = 'https://open.feishu.cn/open-apis/message/v4/send/';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->getToken()
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'msg_type' => 'text',
                'content' => [
                    'text' => $message
                ]
            ] + $this->channel));

        $result = Curl\Util::execute($ch);
        $result = json_decode($result, true);

        if ($result['code'] !== 0) {
            throw new RuntimeException('Feishu API error: ' . $result['msg']);
        }
    }

    protected function getToken() : string
    {
        if (!isset($this->token)) {
            $this->token = $this->cache->get('feishu_token', function (ItemInterface $item) {
                $ch = curl_init();
                $url = 'https://open.feishu.cn/open-apis/auth/v3/tenant_access_token/internal/';
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json'
                ));
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                    "app_id" => $this->appId,
                    "app_secret" => $this->appSecret
                ]));
                $result = Curl\Util::execute($ch);
                $result = json_decode($result, true);
                if ($result['code'] !== 0) {
                    throw new RuntimeException('Feishu API error: ' . $result['msg']);
                }
                $item->expiresAfter($result['expire'] - 600);
                return $result['tenant_access_token'];
            });
        }
        return $this->token;
    }
}