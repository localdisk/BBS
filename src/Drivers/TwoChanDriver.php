<?php

namespace Localdisk\BBS\Drivers;

/**
 * TwoChanProvider
 *
 * @author localdisk
 */
class TwoChanDriver extends AbstractDriver
{

    /**
     * {@inheritdoc}
     */
    public function threads()
    {
        $host     = parse_url($this->url, PHP_URL_HOST);
        $url      = "http://{$host}/{$this->boardNo()}/subject.txt";
        $response = \Requests::get($url);
        if ($response->status_code >= 400) {
            throw new \Exception('書き込みに失敗しました');
        }
        $body    = $this->encode($response->body, 'UTF-8', 'Shift_JIS');
        $threads = array_filter(explode("\n", $body), 'strlen');

        return array_map(function ($elem) use ($host) {
            list($id, $tmp) = explode('.dat<>', $elem);
            preg_match('/^(.*)\(([0-9]+)\)\z/', $tmp, $matches);

            return [
                'id'    => $id,
                'title' => trim($matches[1]),
                'count' => $matches[2],
                'url'   => "http://{$host}/test/read.cgi/{$this->boardNo()}/$id",
            ];
        }, $threads);
    }

    /**
     * {@inheritdoc}
     */
    public function comments($start = null, $end = null, array $headers = [])
    {
        $host     = parse_url($this->url, PHP_URL_HOST);
        $url      = "http://{$host}/{$this->boardNo()}/dat/{$this->threadNo()}.dat";
        $response = \Requests::get($url, $headers, [
            'timeout' => 30,
        ]);
        $body     = null;
        // 304 なら更新なし
        if ($response->status_code === 304) {
            return [];
        }
        // 正常
        if ($response->status_code === 200) {
            $body           = $this->encode($response->body, 'UTF-8', 'Shift_JIS');
            $this->size     = (int)$response->headers['content-length'];
            $this->modified = $response->headers['last-modified'];
        }
        // 304 以外は多分過去ログ
        if ($response->status_code >= 300 && $response->status_code < 500) {
            $four            = substr($this->threadNo(), 0, 4);
            $five            = substr($this->threadNo(), 0, 5);
            $storageUrl      = "http://{$host}/{$this->boardNo()}/kako/{$four}/{$five}/{$this->threadNo()}.dat";
            $storageResponse = \Requests::get($storageUrl);
            $body            = $this->encode($storageResponse->body, 'UTF-8', 'Shift_JIS');
        }

        return $this->parseDat($body);
    }

    /**
     * {@inheritdoc}
     */
    public function parseDat($body)
    {
        $lines  = array_filter(explode("\n", $body), 'strlen');
        $number = 0;
        $url    = $this->url;

        return array_map(function ($line) use (&$number, $url) {
            $number++;
            list($name, $email, $date, $body) = explode('<>', $line);
            $name  = strip_tags($name);
            $body  = strip_tags($body, '<br>');
            $resid = mb_substr($date, strpos($date, ' ID:') + 2);
            $date  = mb_substr($date, 0, strpos($date, ' ID:') - 2);

            return compact('number', 'name', 'email', 'date', 'body', 'resid', 'url');
        }, $lines);
    }

    /**
     * {@inheritdoc}
     */
    public function parseHtml($body)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function post($name = '', $email = 'sage', $text = '')
    {
        mb_convert_variables('Shift_JIS', 'UTF-8', $name, $email, $text);
        $host     = parse_url($this->url, PHP_URL_HOST);
        $params   = [
            'bbs'     => $this->boardNo(),
            'key'     => $this->threadNo(),
            'time'    => time(),
            'FROM'    => $name,
            'mail'    => $email,
            'MESSAGE' => $text,
            'submit'  => $this->encode('書き込む', 'Shift_JIS', 'UTF-8'),
        ];
        $headers  = [
            'Host'       => parse_url($this->url, PHP_URL_HOST),
            'Referer'    => $this->url,
            'useragent' => $this->userAgent,
        ];
        $response = \Requests::post("http://{$host}/test/bbs.cgi", $headers, $params);
        $html     = $this->encode($response->body, 'UTF-8', 'Shift_JIS');
        if ($this->confirm($html)) {
            // 再投稿
            $options = [
                'cookies' => [$response->headers['set-cookie'],
                ],
            ];
            \Requests::post("http://{$host}/test/bbs.cgi", $headers, $this->recreateParams($html), $options);
        }
    }

    /**
     * 書き込み確認かどうか
     *
     * @param  string $html
     *
     * @return boolean
     */
    private function confirm($html)
    {
        return strpos($html, '書き込み確認') !== false;
    }

    private function recreateParams($html)
    {
        $dom = new \DOMDocument;
        @$dom->loadHTML($html);
        $inputs = $dom->getElementsByTagName('input');

        $params = [];
        /* @var $input \DOMElement */
        foreach ($inputs as $input) {
            $value = $this->encode($input->getAttribute('value'), 'Shift_JIS', 'UTF-8');
            if (strtolower($input->getAttribute('type')) === 'submit') {
                $params['submit'] = $value;
            } else {
                $params[$input->getAttribute('name')] = $value;
            }
        }

        return $params;
    }

    /**
     * {@inheritdoc}
     */
    public function category()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function boardNo()
    {
        return $this->segment(3) ?: $this->segment(1);
    }

    /**
     * {@inheritdoc}
     */
    public function threadNo()
    {
        return $this->segment(4);
    }

}
