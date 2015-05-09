<?php

/*
 * The MIT License
 *
 * Copyright 2015 localdisk.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace Localdisk\BBS\Providers;

use Symfony\Component\DomCrawler\Crawler;

/**
 * Description of ShitarabaProvider
 *
 * @author localdisk
 */
class ShitarabaProvider extends AbstractProvider
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

        return array_map(function($elem)
        {
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
        return array_map(function($line)
        {
            list($no, $name, $mail, $date, $text,, $id) = explode('<>', $line);
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
        $result  = $crawler->filter('dt')->each(function(Crawler $node)
        {
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
    public function post($name = '', $email = 'sage', $text = null)
    {
        if (is_null($text)) {
            throw new \InvalidArgumentException('text is null.');
        }
        mb_convert_variables('EUC-JP', 'UTF-8', $name, $email, $text);
        $params   = [
            'DIR'     => $this->category(),
            'BBS'     => $this->boardNo(),
            'NAME'    => $name,
            'MAIL'    => $email,
            'MESSAGE' => $text,
            'KEY'     => $this->threadNo(),
            'submit'  => $this->encode('書き込む', 'UTF-8', 'EUC-JP')
        ];
        $headers  = [
            'Referer'        => $this->url,
            'Connection'     => 'close',
            'Content-Length' => strlen(implode('&', $params))
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
