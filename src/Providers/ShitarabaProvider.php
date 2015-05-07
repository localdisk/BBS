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
        $response = $this->client()->get($url);
        if ($response->getStatusCode() !== 200) {
            // TODO Exception 作成
            throw new \Exception($response->getBody()->getContents(), $response->getStatusCode());
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
        $url      = "{$this->baseUrl}//bbs/rawmode.cgi/{$this->category()}/{$this->boardNo()}/{$this->threadNo()}";
        $response = $this->client()->get($url, ['allow_redirects' => false]);
        if ($response->getStatusCode() > 400) {
            // TODO Exception 作成
            throw new \Exception($response->getBody()->getContents(), $response->getStatusCode());
        }
        $body = $this->encode($response->getBody()->getContents(), 'UTF-8', 'EUC-JP');
        // 過去ログなら
        if ($response->getHeader('ERROR') === 'STORAGE IN') {
            return $this->parseHtml($body);
        }
        return $this->parseDat($body);
    }

    /**
     * {@inheritdoc}
     */
    public function parseDat($body)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function parseHtml($body)
    {

    }

    /**
     * URL からカテゴリを取得する
     *
     * @return string カテゴリ
     */
    public function category()
    {
        return $this->segment(3);
    }

    /**
     * URL から掲示板番号を取得する
     *
     * @return integer
     */
    public function boardNo()
    {
        return $this->segment(4);
    }

    /**
     * URL からスレッド番号を取得する
     *
     * @return integer
     */
    public function threadNo()
    {
        return $this->segment(5);
    }

}
