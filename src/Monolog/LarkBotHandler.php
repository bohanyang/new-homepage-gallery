<?php

namespace App\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Logger;
use RuntimeException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function curl_close;
use function curl_errno;
use function curl_error;
use function curl_exec;
use function in_array;
use function sprintf;

use const CURLE_COULDNT_CONNECT;
use const CURLE_COULDNT_RESOLVE_HOST;
use const CURLE_HTTP_NOT_FOUND;
use const CURLE_HTTP_POST_ERROR;
use const CURLE_OPERATION_TIMEOUTED;
use const CURLE_READ_ERROR;
use const CURLE_SSL_CONNECT_ERROR;

class LarkBotHandler extends AbstractProcessingHandler
{
    public const API_CN = 'https://open.feishu.cn';
    public const API_GLOBAL = 'https://open.larksuite.com';
    private $appId;
    private $appSecret;
    private $channel;
    /** @var CacheInterface */
    private $cache;
    private $token;
    private $apiHost;

    public function __construct(

        string $appId,
        string $appSecret,
        array $channel,
        CacheInterface $cache,
        string $apiHost = self::API_CN,
        $level = Logger::DEBUG,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);

        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->channel = $channel;
        $this->cache = $cache;
        $this->level = $level;
        $this->bubble = $bubble;
        $this->apiHost = $apiHost;
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
        $url = $this->apiHost . '/open-apis/message/v4/send/';
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->getToken()
            )
        );
        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode(
                [
                    'msg_type' => 'text',
                    'content' => [
                        'text' => $message
                    ]
                ] + $this->channel
            )
        );

        $result = self::execute($ch);
        $result = json_decode($result, true);

        if ($result['code'] !== 0) {
            throw new RuntimeException('Lark API error: ' . $result['msg']);
        }
    }

    protected function getToken() : string
    {
        if (!isset($this->token)) {
            $this->token = $this->cache->get(
                'lark_token',
                function (ItemInterface $item) {
                    $ch = curl_init();
                    $url = $this->apiHost . '/open-apis/auth/v3/tenant_access_token/internal/';
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt(
                        $ch,
                        CURLOPT_HTTPHEADER,
                        array(
                            'Content-Type: application/json'
                        )
                    );
                    curl_setopt(
                        $ch,
                        CURLOPT_POSTFIELDS,
                        json_encode(
                            [
                                "app_id" => $this->appId,
                                "app_secret" => $this->appSecret
                            ]
                        )
                    );
                    $result = self::execute($ch);
                    $result = json_decode($result, true);
                    if ($result['code'] !== 0) {
                        throw new RuntimeException('Lark API error: ' . $result['msg']);
                    }
                    $item->expiresAfter($result['expire'] - 600);
                    return $result['tenant_access_token'];
                }
            );
        }
        return $this->token;
    }

    protected static function execute($ch, int $retries = 5, bool $closeAfterDone = true)
    {
        while ($retries--) {
            $curlResponse = curl_exec($ch);
            if ($curlResponse === false) {
                $curlErrno = curl_errno($ch);

                if (false === in_array(
                        $curlErrno,
                        [
                            CURLE_COULDNT_RESOLVE_HOST,
                            CURLE_COULDNT_CONNECT,
                            CURLE_HTTP_NOT_FOUND,
                            CURLE_READ_ERROR,
                            CURLE_OPERATION_TIMEOUTED,
                            CURLE_HTTP_POST_ERROR,
                            CURLE_SSL_CONNECT_ERROR,
                        ],
                        true
                    ) || !$retries) {
                    $curlError = curl_error($ch);

                    if ($closeAfterDone) {
                        curl_close($ch);
                    }

                    throw new \RuntimeException(sprintf('Curl error (code %d): %s', $curlErrno, $curlError));
                }

                continue;
            }

            if ($closeAfterDone) {
                curl_close($ch);
            }

            return $curlResponse;
        }

        return false;
    }
}
