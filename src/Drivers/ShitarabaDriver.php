<?php


namespace Localdisk\BBS\Drivers;

/**
 * Description of ShitarabaProvider
 *
 * @author localdisk
 */
class ShitarabaDriver extends AbstractDriver
{

    /**
     * したらばのURL
     *
     * @var string baseurl
     */
    protected $baseUrl = 'http://jbbs.shitaraba.net';

    /**
     * {@inheritdoc}
     */
    public function threads()
    {
        $url      = "{$this->baseUrl}/{$this->category()}/{$this->boardNo()}/subject.txt";
        $response = \Requests::get($url);
        if ($response->status_code >= 400) {
            throw new \Exception('スレッド読み込みに失敗しました');
        }
        $body    = $this->encode($response->body, 'UTF-8', 'EUC-JP');
        $threads = array_filter(explode("\n", $body), 'strlen');

        return array_map(function ($elem) {
            list($id, $tmp) = explode('.cgi,', $elem);
            preg_match('/^(.*)\(([0-9]+)\)\z/', $tmp, $matches);

            return [
                'id'    => $id,
                'title' => trim($matches[1]),
                'count' => $matches[2],
                'url'   => "{$this->baseUrl}/bbs/read.cgi/{$this->category()}/{$this->boardNo()}/$id",
            ];
        }, $threads);
    }

    /**
     * {@inheritdoc}
     */
    public function comments($start = null, $end = null, array $headers = [])
    {
        $url      = $this->createCommentsUrl($start, $end);
        $response = \Requests::get($url, [], [
            'timeout' => 10,
        ]);
        $body     = $this->encode($response->body, 'UTF-8', 'EUC-JP');
        // 過去ログなら
        if ($response->headers['error'] === 'STORAGE IN') {
            $storageUrl = "{$this->baseUrl}/bbs/read_archive.cgi/{$this->category()}/{$this->boardNo()}/{$this->threadNo()}/";
            $storageRes = \Requests::get($storageUrl);
            $html       = $this->encode($storageRes->body, 'UTF-8', 'EUC-JP');

            return $this->parseHtml($html);
        }

        return $this->parseDat($body);
    }

    /**
     * {@inheritdoc}
     */
    public function post($name = '', $email = 'sage', $text = '')
    {
        mb_convert_variables('EUC-JP', 'UTF-8', $name, $email, $text);
        $params   = [
            'submit'  => $this->encode('書き込む', 'EUC-JP', 'UTF-8'),
            'DIR'     => $this->category(),
            'BBS'     => $this->boardNo(),
            'KEY'     => $this->threadNo(),
            'TIME'    => time(),
            'MESSAGE' => $text,
            'NAME'    => $name,
            'MAIL'    => $email,
        ];
        $headers  = [
            'Host'            => parse_url($this->url, PHP_URL_HOST),
            'Referer'         => $this->url,
            'Accept-Encoding' => 'gzip ,deflate',
        ];
        $options  = [
            'cookies'   => [
                'NAME'  => $name,
                'EMAIL' => $email,
                'Path'  => '/',
            ],
            'useragent' => $this->userAgent,
        ];
        $response = \Requests::post("{$this->baseUrl}/bbs/write.cgi", $headers, $params, $options);
        if ($response->status_code >= 400) {
            throw new \Exception('書き込みに失敗しました');
        }

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function parseDat($body)
    {
        $lines = array_filter(explode("\n", $body), 'strlen');

        $this->size = count($lines);
        $url        = $this->url;

        return array_map(function ($line) use ($url) {
            list($number, $name, $email, $date, $body, , $resid) = explode('<>', $line);
            $name = strip_tags($name);
            $body = strip_tags($body, '<br>');
            return compact('number', 'name', 'email', 'date', 'body', 'resid', 'url');
        }, $lines);
    }

    /**
     * {@inheritdoc}
     */
    public function parseHtml($body)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($body);
        $url    = $this->url;
        $result = $crawler->filter('dt')->each(function (Crawler $node) use ($url) {
            list($number, $name, $other) = explode('：', $node->text());
            $name   = strip_tags($name);
            $number = trim($number);
            if (count($node->filter('a[href*=mailto]'))) {
                $href  = $node->filter('a[href*=mailto]')->attr('href');
                $email = substr($href, strpos($href, ':') + 1);
            } else {
                $email = '';
            }
            $date  = trim(substr($other, 0, strpos($other, 'ID')));
            $body  = strip_tags($node->nextAll()->first()->html(), '<br>');
            $resid = substr($other, strpos($other, 'ID') + 3);

            return compact('number', 'name', 'email', 'date', 'body', 'resid', 'url');
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function category()
    {
        return $this->segment(3) ?: $this->segment(1);
    }

    /**
     * {@inheritdoc}
     */
    public function boardNo()
    {
        return $this->segment(4) ?: $this->segment(2);
    }

    /**
     * {@inheritdoc}
     */
    public function threadNo()
    {
        return $this->segment(5);
    }

    private function createCommentsUrl($start, $end)
    {
        $url = "{$this->baseUrl}/bbs/rawmode.cgi/{$this->category()}/{$this->boardNo()}/{$this->threadNo()}/";
        if (!is_null($start) && !is_null($end)) {
            $start++;

            return $url . "{$start}-{$end}";
        }
        if (!is_null($start) && is_null($end)) {
            $start++;

            return $url . "{$start}-";
        }
        if (is_null($start) && !is_null($end)) {
            return $url . "-{$end}";
        }

        return $url;
    }
}
