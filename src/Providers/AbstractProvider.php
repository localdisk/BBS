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
 * Abstract BBS Provider
 *
 * @author localdisk
 */
abstract class AbstractProvider
{

    /**
     * URL
     *
     * @var string url
     */
    private $url;

    /**
     * コンストラクタ
     *
     * @param string $url URL
     */
    public function __construct($url)
    {
        $this->url = $url;
    }

    /**
     * Get a fresh instance of the Guzzle HTTP client.
     *
     * @return GuzzleHttp\Client
     */
    public function client()
    {
        return new \GuzzleHttp\Client(['defaults' => ['headers' => ['User-Agent' => 'yarana.io']]]);
    }

    /**
     * url のセグメントを取得
     *
     * @param  int  $index
     * @param  mixed  $default
     * @return string
     */
    public function segment($index, $default = null)
    {
        return array_get($this->segments(), $index - 1, $default);
    }

    /**
     * URL のパスを配列に分解
     *
     * @return array
     */
    public function segments()
    {
        $segments = explode('/', parse_url($this->url, PHP_URL_PATH));

        return array_values(array_filter($segments, function($v)
        {
            return $v != '';
        }));
    }

    /**
     * 文字列のエンコード処理
     *
     * @param  string $str
     * @param  string $to
     * @param  string $from
     * @return string
     */
    public function encode($str, $to, $from)
    {
        return mb_convert_encoding($str, $to, $from);
    }

    /**
     * スレッドの一覧を取得する
     *
     * @return array スレッド一覧
     */
    abstract function threads();

    /**
     * スレッドの内容を取得する
     *
     * @param  integer $start 開始レス番
     * @param  integer $end   終了レス番
     * @return array スレッドの内容
     */
    abstract function comments($start = null, $end = null);

    /**
     * HTML をパースする
     *
     * @param  string $body HTML
     * @return array 解析結果
     */
    abstract function parseHtml($body);

    /**
     * DAT をパースする
     *
     * @param  string $body HTML
     * @return array 解析結果
     */
    abstract function parseDat($body);
}
