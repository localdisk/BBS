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

namespace Localdisk\BBS;

use Illuminate\Support\Manager;

/**
 * BbsManager
 *
 * @author localdisk
 */
class BBSManager extends Manager
{

    /**
     * Guzzle Client
     *
     * @var \GuzzleHttp\Client
     */
    protected $client;

    /**
     * コンストラクタ
     *
     * @param \Illuminate\Foundation\Application $app
     */
    public function __construct($app, $client)
    {
        $this->app    = $app;
        $this->client = $client;
    }
    /**
     * Get a provider instance.
     *
     * @param  string $url
     * @return Providers\AbstractProvider
     */
    public function url($url)
    {
        if (parse_url($url, PHP_URL_HOST) === 'jbbs.shitaraba.net') {
            return $this->createShitarabaProvider($url);
        }
        return $this->createTwoChanProvider($url);
    }

    /**
     * Create Shitaraba Provider
     *
     * @param  string $url
     * @return Providers\AbstractProvider
     */
    public function createShitarabaProvider($url)
    {
        return $this->buildProvider(Providers\ShitarabaProvider::class, $url);
    }

    /**
     * Create TwoChan Provider
     *
     * @param  string $url
     * @return Providers\AbstractProvider
     */
    public function createTwoChanProvider($url)
    {
        return $this->buildProvider(Providers\TwoChanProvider::class, $url);
    }

    /**
     * Build Provider
     *
     * @param  string $provider
     * @param  string $url
     * @return Providers\AbstractProvider
     */
    public function buildProvider($provider, $url)
    {
        return new $provider($this->client, $url);
    }

    /**
     * Get the default driver name.
     *
     * @throws \InvalidArgumentException
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException("No BBS driver was specified.");
    }

}
