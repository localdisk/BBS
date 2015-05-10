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
 * TwoChanProvider
 *
 * @author localdisk
 */
class TwoChanProvider extends AbstractProvider
{
    /**
     * {@inheritdoc}
     */
    public function comments($start = null, $end = null)
    {
        $host     = parse_url($this->url, PHP_URL_HOST);
        $url      = "http://{$host}/{$this->boardNo()}/dat/{$this->threadNo()}.dat";
        $response = $this->client->get($url, ['exceptions' => false]);
        $body     = $this->encode($response->getBody()->getContents(), 'UTF-8', 'Shift_JIS');
        // 過去ログなら
        if ($response->getStatusCode() >= 300 || $response->getStatusCode() < 500) {
            $four = substr($this->threadNo(), 0, 4);
            $five = substr($this->threadNo(), 0, 5);
            $storageUrl = "http://{$host}/{$this->boardNo()}/kako/{$four}/{$five}/{$this->threadNo()}.dat";
            $response = $this->client->get($storageUrl);
            $body     = $this->encode($response->getBody()->getContents(), 'UTF-8', 'Shift_JIS');
        }
        return $this->parseDat($body);
    }

    /**
     * {@inheritdoc}
     */
    public function parseDat($body)
    {
        $lines = array_filter(explode("\n", $body), 'strlen');
        $no = 0;
        return array_map(function($line) use (&$no)
        {
            $no++;
            list($name, $mail, $date, $text) = explode('<>', $line);
            $id   = mb_substr($date, strpos($date, ' ID:') + 2);
            $date = mb_substr($date, 0, strpos($date, ' ID:') - 2);
            return compact('no', 'name', 'mail', 'date', 'text', 'id');
        }, $lines);

    }

    /**
     * {@inheritdoc}
     */
    public function parseHtml($body)
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function post($name = '', $email = 'sage', $text = null)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function threads()
    {
        $host     = parse_url($this->url, PHP_URL_HOST);
        $url      = "http://{$host}/{$this->boardNo()}/subject.txt";
        $response = $this->client->get($url);
        if ($response->getStatusCode() >= 400) {
            throw new \Exception('書き込みに失敗しました');
        }
        $body    = $this->encode($response->getBody()->getContents(), 'UTF-8', 'Shift_JIS');
        $threads = array_filter(explode("\n", $body), 'strlen');

        return array_map(function($elem)
        {
            list($id, $tmp) = explode('.dat<>', $elem);
            preg_match('/^(.*)\(([0-9]+)\)\z/', $tmp, $matches);
            return ['id' => $id, 'title' => trim($matches[1]), 'count' => $matches[2]];
        }, $threads);
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
        return $this->segment(3);
    }

    /**
     * {@inheritdoc}
     */
    public function threadNo()
    {
        return $this->segment(4);
    }

}
