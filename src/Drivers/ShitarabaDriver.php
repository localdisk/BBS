<?php


namespace Localdisk\BBS\Drivers;

use Symfony\Component\DomCrawler\Crawler;

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
        $response = $this->client->get($url);
        if ($response->getStatusCode() >= 400) {
            throw new \Exception('書き込みに失敗しました');
        }
        $body    = $this->encode($response->getBody()->getContents(), 'UTF-8', 'EUC-JP');
        $threads = array_filter(explode("\n", $body), 'strlen');

        return array_map(function ($elem) {
            list($id, $tmp) = explode('.cgi,', $elem);
            preg_match('/^(.*)\(([0-9]+)\)\z/', $tmp, $matches);

            return ['id' => $id, 'title' => trim($matches[1]), 'count' => $matches[2]];
        }, $threads);
    }

    /**
     * {@inheritdoc}
     */
    public function comments($start = null, $end = null)
    {
        $url      = "{$this->baseUrl}/bbs/rawmode.cgi/{$this->category()}/{$this->boardNo()}/{$this->threadNo()}/";
        $response = $this->client->get($url);
        $body     = $this->encode($response->getBody()->getContents(), 'UTF-8', 'EUC-JP');
        // 過去ログなら
        if ($response->getHeader('ERROR') === 'STORAGE IN') {
            $storageUrl = "{$this->baseUrl}/bbs/read_archive.cgi/{$this->category()}/{$this->boardNo()}/{$this->threadNo()}/";
            $storageRes = $this->client->get($storageUrl);
            $html       = $this->encode($storageRes->getBody()->getContents(), 'UTF-8', 'EUC-JP');

            return $this->parseHtml($html);
        }

        return $this->parseDat($body);
    }

    /**
     * {@inheritdoc}
     */
    public function parseDat($body)
    {
        $lines = array_filter(explode("\n", $body), 'strlen');

        return array_map(function ($line) {
            list($no, $name, $mail, $date, $text, , $id) = explode('<>', $line);

            return compact('no', 'name', 'mail', 'date', 'text', 'id');
        }, $lines);
    }

    /**
     * {@inheritdoc}
     */
    public function parseHtml($body)
    {
        $crawler = new Crawler();
        $crawler->addHtmlContent($body);
        $result = $crawler->filter('dt')->each(function (Crawler $node) {
            list($no, $name, $other) = explode('：', $node->text());
            $no = trim($no);
            if (count($node->filter('a[href*=mailto]'))) {
                $href  = $node->filter('a[href*=mailto]')->attr('href');
                $email = substr($href, strpos($href, ':') + 1);
            } else {
                $email = '';
            }
            $date = trim(substr($other, 0, strpos($other, 'ID')));
            $text = $node->nextAll()->first()->html();
            $id   = substr($other, strpos($other, 'ID') + 3);

            return compact('no', 'name', 'email', 'date', 'text', 'id');
        });

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function post($name = '', $email = 'sage', $text = '')
    {
        mb_convert_variables('EUC-JP', 'UTF-8', $name, $email, $text);
        $params   = [
            'DIR'     => $this->category(),
            'BBS'     => $this->boardNo(),
            'NAME'    => $name,
            'MAIL'    => $email,
            'MESSAGE' => $text,
            'KEY'     => $this->threadNo(),
            'submit'  => $this->encode('書き込む', 'EUC-JP', 'UTF-8'),
        ];
        $headers  = [
            'Referer'        => $this->url,
            'Connection'     => 'close',
            'Content-Length' => strlen(implode('&', $params)),
        ];
        $response = $this->client->post("{$this->baseUrl}/bbs/write.cgi", [
            'headers' => $headers,
            'body'    => $params,
        ]);
        if ($response->getStatusCode() >= 400) {
            throw new \Exception('書き込みに失敗しました');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function category()
    {
        return $this->segment(3);
    }

    /**
     * {@inheritdoc}
     */
    public function boardNo()
    {
        return $this->segment(4);
    }

    /**
     * {@inheritdoc}
     */
    public function threadNo()
    {
        return $this->segment(5);
    }

}
