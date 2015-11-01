<?php

namespace Localdisk\BBS\Drivers;

/**
 * Abstract BBS Provider
 *
 * @author localdisk
 */
abstract class AbstractDriver
{
    /**
     * URL
     *
     * @var string
     */
    protected $url;

    /**
     * Size
     *
     * @var int
     */
    protected $size = 0;

    /**
     * Modified
     *
     * @var string
     */
    protected $modified = '';

    /**
     * コンストラクタ
     *
     * @param string             $url
     */
    public function __construct($url)
    {
        $this->url    = $url;
    }

    /**
     * url のセグメントを取得
     *
     * @param  int   $index
     * @param  mixed $default
     *
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

        return array_values(array_filter($segments, function ($v) {
            return $v != '';
        }));
    }

    /**
     * 文字列のエンコード処理
     *
     * @param  string $str
     * @param  string $to
     * @param  string $from
     *
     * @return string
     */
    public function encode($str, $to, $from)
    {
        return mb_convert_encoding($str, $to, $from);
    }

    /**
     * サイズを取得する
     *
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * 最終更新日時を取得する
     *
     * @return string
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * URL を取得する
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
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
     * @param  integer $start   開始レス番
     * @param  integer $end     終了レス番
     * @param  array   $headers ヘッダ
     *
     * @return array スレッドの内容
     */
    abstract function comments($start = null, $end = null, array $headers = []);

    /**
     * HTML をパースする
     *
     * @param  string $body HTML
     *
     * @return array 解析結果
     */
    abstract function parseHtml($body);

    /**
     * DAT をパースする
     *
     * @param  string $body dat
     *
     * @return array 解析結果
     */
    abstract function parseDat($body);

    /**
     * 書き込みする
     *
     * @param  string $name
     * @param  string $email
     * @param  string $text
     */
    abstract function post($name = '', $email = 'sage', $text = '');

    /**
     * URL からカテゴリを取得する
     *
     * @return string カテゴリ
     */
    abstract function category();

    /**
     * URL から掲示板番号を取得する
     *
     * @return integer
     */
    abstract function boardNo();

    /**
     * URL からスレッド番号を取得する
     *
     * @return integer
     */
    abstract function threadNo();
}
