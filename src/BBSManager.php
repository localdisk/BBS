<?php

namespace Localdisk\BBS;

use Illuminate\Support\Manager;
use Localdisk\BBS\Drivers\ShitarabaDriver;
use Localdisk\BBS\Drivers\TwoChanDriver;

/**
 * BbsManager
 *
 * @author localdisk
 */
class BBSManager extends Manager
{

    /**
     * コンストラクタ
     *
     * @param  \Illuminate\Contracts\Foundation\Application
     */
    public function __construct($app)
    {
        parent::__construct($app);
    }

    /**
     * Get a provider instance.
     *
     * @param  string $url
     *
     * @return \Localdisk\BBS\Drivers\AbstractDriver
     */
    public function url($url)
    {
        if (parse_url($url, PHP_URL_HOST) === 'jbbs.shitaraba.net') {
            return $this->createShitarabaDriver($url);
        }

        return $this->createTwoChanProvider($url);
    }

    /**
     * Create Shitaraba Driver
     *
     * @param  string $url
     *
     * @return \Localdisk\BBS\Drivers\ShitarabaDriver
     */
    public function createShitarabaDriver($url)
    {
        return $this->buildProvider(ShitarabaDriver::class, $url);
    }

    /**
     * Create TwoChan Provider
     *
     * @param  string $url
     *
     * @return \Localdisk\BBS\Drivers\TwoChanDriver
     */
    public function createTwoChanProvider($url)
    {
        return $this->buildProvider(TwoChanDriver::class, $url);
    }

    /**
     * Build Provider
     *
     * @param  string $provider
     * @param  string $url
     *
     * @return \Localdisk\BBS\Drivers\AbstractDriver
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
        throw new \InvalidArgumentException("No BBS driver was specified.");
    }

}
