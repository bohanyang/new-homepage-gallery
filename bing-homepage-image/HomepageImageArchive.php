<?php

declare(strict_types=1);

namespace BohanYang\BingWallpaper;

use Assert\Assertion;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use GuzzleHttp;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function abs;
use function array_shift;
use function Safe\json_decode;
use function Safe\sprintf;
use function parse_str;
use function parse_url;
use function preg_match;
use function urldecode;

use const PHP_URL_QUERY;

final class HomepageImageArchive
{
    public const TIMEZONES = [
        'ROW' => 'America/Los_Angeles', // UTC -8 / UTC -7
        'en-US' => 'America/Los_Angeles',
        'pt-BR' => 'America/Los_Angeles',
        'en-CA' => 'America/Toronto', // UTC -5 / UTC -4
        'fr-CA' => 'America/Toronto',
        'en-GB' => 'Europe/London', // UTC +0 / UTC +1
        'fr-FR' => 'Europe/Paris', // UTC +1 / UTC +2
        'de-DE' => 'Europe/Berlin',
        'en-IN' => 'Asia/Kolkata', // UTC +5:30
        'zh-CN' => 'Asia/Shanghai', // UTC +8
        'ja-JP' => 'Asia/Tokyo', // UTC +9
        'en-AU' => 'Australia/Sydney', // UTC +10 / UTC +11
    ];

    public static function hasBecomeTheLaterDate(DateTimeZone $tz, ?DateTimeImmutable $now = null) : bool
    {
        $now = self::getCurrentUTC($now);
        $offset = self::getMidnightOffset($now, self::getTheLaterDate($now));
        return self::compareWithMidnightOffset($tz, $now, $offset);
    }

    /**
     * @param array $markets
     * @param DateTimeImmutable $now Current UTC
     * @param int $offset
     * @return array
     */
    public static function getMarketsHaveBecomeTheLaterDate(array $markets, DateTimeImmutable $now, int $offset) : array
    {
        $timezones = [];
        $tzHasBecome = [];
        $results = [];
        foreach ($markets as $market) {
            if (!isset(self::TIMEZONES[$market])) {
                new InvalidArgumentException('Timezone is unknown for market ' . $market);
            }
            $tz = self::TIMEZONES[$market];
            if (!isset($timezones[$tz])) {
                $timezones[$tz] = new DateTimeZone($tz);
                $tzHasBecome[$tz] = self::compareWithMidnightOffset($timezones[$tz], $now, $offset);
            }
            if ($tzHasBecome[$tz]) {
                $results[0][] = $market;
                $results[1][$market] = $timezones[$tz];
            }
        }
        return $results;
    }

    public static function getCurrentUTC(?DateTimeImmutable $now = null) : DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        return $now === null ?
            new DateTimeImmutable('now', $utc) :
            $now->setTimezone($utc);
    }

    public static function getTheLaterDate(DateTimeImmutable $now) : DateTimeImmutable
    {
        if ((int) $now->format('G') < 12) {
            // For timezones later than UTC (have an earlier date),
            // the moment of date change is 00:00 on today's date of UTC
            return $now->setTime(0, 0, 0);
        } else {
            // For timezones earlier than UTC (have an later date),
            // the moment of date change is 00:00 on tomorrow's date of UTC
            return $now->modify('+1 day midnight');
        }
    }

    /**
     * @param DateTimeImmutable $now Current UTC
     * @param DateTimeImmutable $d The later date
     */
    public static function getMidnightOffset(DateTimeImmutable $now, DateTimeImmutable $d) : int
    {
        // General solution:
        // $now->diff($the_moment_of_date_change)
        // which is equivalent to
        // $the_moment_of_date_change - $now
        return $d->getTimestamp() - $now->getTimestamp();
    }

    /**
     * @param DateTimeZone $tz Timezone to be compared
     * @param DateTimeImmutable $now Current UTC
     * @param int $offset Midnight offset
     */
    private static function compareWithMidnightOffset(DateTimeZone $tz, DateTimeImmutable $now, int $offset) : bool
    {
        return (int) $now->setTimezone($tz)->format('Z') >= $offset;
    }

    public static function getToday(?DateTimeZone $tz = null, ?DateTimeImmutable $today = null) : DateTimeImmutable
    {
        if ($today === null) {
            $today = new DateTimeImmutable('today', $tz);
        } else {
            $tz = $tz ?? $today->getTimezone();
            $today = $today->setTimezone($tz)->setTime(0, 0, 0);
        }

        return $today;
    }

    /**
     * Get how many days ago was "$date".
     */
    public static function daysAgo(DateTimeImmutable $date, ?DateTimeImmutable $today = null) : int
    {
        $today = self::getToday($date->getTimezone(), $today);
        $diff = $date->setTime(0, 0, 0)->diff($today, false);

        return (int) $diff->format('%r%a');
    }

    /**
     * Get the date "$index" days before today in "$tz".
     */
    public static function dateBefore(
        int $index,
        ?DateTimeZone $tz = null,
        ?DateTimeImmutable $today = null
    ) : DateTimeImmutable {
        $today = self::getToday($tz, $today);
        $invert = $index < 0 ? 1 : 0;
        $index = (string) abs($index);
        $interval = new DateInterval("P${index}D");
        $interval->invert = $invert;

        return $today->sub($interval);
    }

    /**
     * Parse "fullstartdate" string into DateTime
     * with correct time zone of UTC offset type.
     */
    public static function parseFullStartDate(string $fullStartDate) : DateTimeImmutable
    {
        $d = DateTimeImmutable::createFromFormat('YmdHi', $fullStartDate, new DateTimeZone('UTC'));

        if ($d === false) {
            throw new InvalidArgumentException("Failed to parse full start date ${fullStartDate}.");
        }

        if ((int) $d->format('G') < 12) {
            // The moment of date change is the new date's 00:00
            // and UTC is on the new date.
            // Therefore, the timezone just reached the new date's 00:00
            // (just changed its date / just becomes the next day)
            // is slower than UTC.
            $tz = '-' . $d->format('H:i');
        } else {
            // But when UTC becomes 12:00, all UTC -* timezones
            // (the west side of the prime meridian)
            // already changed their date.
            // The fastest UTC +12 becomes the next new date
            // (tomorrow's date of UTC).
            $d24 = $d->modify('+1 day midnight');
            $tz = $d->diff($d24, true)->format('%R%H:%I');
            $d = $d24;
        }

        return new DateTimeImmutable($d->format('Y-m-d'), new DateTimeZone($tz));
    }

    /**
     * Parse an URL of web search engine and
     * extract keyword from its query string.
     */
    public static function extractKeyword(string $url) : ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);

        if (!$query) {
            return null;
        }

        parse_str($query, $query);

        $fields = ['q', 'wd'];

        foreach ($fields as $field) {
            if (isset($query[$field]) && $query[$field] !== '') {
                return urldecode($query[$field]);
            }
        }

        return null;
    }

    /**
     * Normalize "urlbase" and extract image name from it.
     *
     * @param string $urlBase e.g.
     *  "/az/hprichbg/rb/BemarahaNP_JA-JP15337355971" or
     *  "/th?id=OHR.BemarahaNP_JA-JP15337355971"
     *
     * @return string[] e.g.
     *  [
     *      "BemarahaNP_JA-JP15337355971",
     *      "BemarahaNP",
     *      "JA-JP15337355971"
     *  ]
     */
    public static function parseUrlBase(string $urlBase)
    {
        $regex = '/(\w+)_((?:ROW|[A-Z]{2}-[A-Z]{2})\d+)/';
        $matches = [];

        if (preg_match($regex, $urlBase, $matches) !== 1) {
            throw new InvalidArgumentException("Failed to parse URL base ${urlBase}.");
        }

        return $matches;
    }

    /**
     * Extract image description as well as the author and/or
     * the stock photo agency from "copyright" string.
     *
     * @return string[] [$description, $copyright]
     */
    public static function parseCopyright(string $copyright)
    {
        $regex = '/(.+?)(?: |\x{3000})?(?:\(|\x{FF08})?\x{00A9}(?: |\x{3000})?(.+?)(?:\)|\x{FF09})?$/u';
        $matches = [];

        if (preg_match($regex, $copyright, $matches) !== 1) {
            throw new InvalidArgumentException("Failed to parse copyright string ${copyright}.");
        }

        array_shift($matches);

        return $matches;
    }

    /** @var string */
    private $endpoint;

    /** @var Client */
    private $client;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        string $endpoint = 'https://global.bing.com/HPImageArchive.aspx',
        ?LoggerInterface $logger = null,
        ?callable $handler = null,
        ?MessageFormatter $formatter = null
    ) {
        $this->endpoint = $endpoint;
        $this->logger = $logger ?? new Logger(self::class, [new StreamHandler('php://stderr')]);
        $handler = $handler ?? GuzzleHttp\choose_handler();
        $formatter = $formatter ?? new MessageFormatter();

        $handler = new HandlerStack($handler);
        $handler->push(Middleware::httpErrors(), 'http_errors');
        $handler->push(Middleware::redirect(), 'allow_redirects');
        $handler->push(GuzzleMiddleware::retry(), 'retry');
        $handler->push(Middleware::log($this->logger, $formatter, LogLevel::DEBUG));

        $this->client = new Client(['handler' => $handler]);
    }

    private function request(string $market, int $index = 0, int $n = 1) : PromiseInterface
    {
        return $this->client->getAsync(
            $this->endpoint,
            [
                'query' => [
                    'format' => 'js',
                    'idx' => (string) $index,
                    'n' => (string) $n,
                    'video' => '1',
                    'mkt' => $market,
                ],
            ]
        );
    }

    private function get(string $market, ?DateTimeImmutable $date = null, ?DateTimeZone $tz = null) : PromiseInterface
    {
        if ($tz === null) {
            if (!isset(self::TIMEZONES[$market])) {
                return new RejectedPromise(
                    new InvalidArgumentException(
                        "Market ${market} is unknown and no timezone provided"
                    )
                );
            }

            $tz = new DateTimeZone(self::TIMEZONES[$market]);
        }

        $date = $date ? new DateTimeImmutable($date->format('Y-m-d'), $tz) : self::getToday($tz);
        $offset = self::daysAgo($date);

        if ($offset < 0 || $offset > 7) {
            return new RejectedPromise(
                new InvalidArgumentException(
                    sprintf(
                        'The date %s in timezone %s (UTC %s) has offset %d which is out of the available range (0 to 7)',
                        $date->format('Y-m-d'),
                        $tz->getName(),
                        $date->format('P'),
                        $offset
                    )
                )
            );
        }

        return $this->request($market, $offset)->then(
            function (ResponseInterface $resp) use ($market, $date, $offset, $tz) {
                $resp = (string) $resp->getBody();
                $resp = json_decode($resp, true);
                if (empty($resp['images'][0])) {
                    throw new UnexpectedValueException(
                        "Empty response for ${market} on {$date->format('Y-m-d')} (offset ${offset})"
                    );
                }

                try {
                    $resp = self::parseResponse($resp['images'][0], $market);
                } catch (Throwable $e) {
                    throw new UnexpectedValueException(
                        "Failed to parse response for ${market} on {$date->format('Y-m-d')} (offset ${offset})",
                        0, $e
                    );
                }

                if ($resp['date']->format('Y-m-d') !== $date->format('Y-m-d')) {
                    throw new UnexpectedValueException(
                        "Got unexpected date {$resp['date']->format('Y-m-d')} (UTC " .
                        $resp['date']->format('P') .
                        ") instead of {$date->format('Y-m-d')} (market=${market}, offset=${offset})"
                    );
                }

                if ($resp['date']->format('Z') !== $date->format('Z')) {
                    $this->logger->warning('The actual timezone offset differs from expected', [
                        'market' => $market,
                        'date' => $date,
                        'actual' => $resp['date'],
                    ]);
                }

                return $resp;
            }
        );
    }

    private const REQUIRED_FIELDS = [
        'fullstartdate',
        'urlbase',
        'copyright',
        'copyrightlink',
        'wp'
    ];

    /**
     * @return array Result structure:
     *  - market (required, string)
     *  - date (required, DateTimeImmutable)
     *  - description (required, string)
     *  - link (optional, string)
     *  - hotspots (optional)
     *  - messages (optional)
     *  - coverstory (optional)
     *  - image (required)
     *      - name (required, string)
     *      - urlbase (required, string, e.g. "/az/hprichbg/rb/BemarahaNP_JA-JP15337355971")
     *      - copyright (required, string)
     *      - wp (required, boolean)
     *      - vid (optional)
     */
    private static function parseResponse(array $resp, string $market) : array
    {
        $result = [];
        $result['market'] = $market;

        foreach (self::REQUIRED_FIELDS as $field) {
            if (!isset($resp[$field]) || $resp[$field] === '') {
                throw new InvalidArgumentException("Required field $field does not exist in response");
            }
        }

        $result['date'] = self::parseFullStartDate($resp['fullstartdate']);

        [$result['image']['urlbase'], $result['image']['name']] = self::parseUrlBase($resp['urlbase']);
        $result['image']['urlbase'] = '/az/hprichbg/rb/' . $result['image']['urlbase'];

        [$result['description'], $result['image']['copyright']] = self::parseCopyright($resp['copyright']);

        if ($resp['copyrightlink'] !== 'javascript:void(0)') {
            Assertion::url($resp['copyrightlink']);
            $result['link'] = $resp['copyrightlink'];
        }

        Assertion::boolean($resp['wp']);
        $result['image']['wp'] = $resp['wp'];

        if (!empty($resp['vid'])) {
            $result['image']['vid'] = $resp['vid'];
        }

        if (!empty($resp['hs'])) {
            $result['hotspots'] = $resp['hs'];
        }

        if (!empty($resp['msg'])) {
            $result['messages'] = $resp['msg'];
        }

        return $result;
    }

    public function fetch(string $market, ?DateTimeImmutable $date = null, ?DateTimeZone $tz = null) : array
    {
        return $this->get($market, $date, $tz)->wait();
    }

    public function batch(array $markets, ?DateTimeImmutable $date = null)
    {
        $date = $date ?? self::getToday(new DateTimeZone('America/Los_Angeles'));

        /** @var PromiseInterface[] $promises */
        $promises = [];

        foreach ($markets as $market) {
            $promises[$market] = $this->get($market, $date);
        }

        $promises = Promise\unwrap($promises);

        return $promises;
    }
}
