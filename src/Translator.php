<?php /** @noinspection PhpUnusedPrivateFieldInspection */
/*
 * This file is a part of "comely-io/translator" package.
 * https://github.com/comely-io/translator
 *
 * Copyright (c) Furqan A. Siddiqui <hello@furqansiddiqui.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code or visit following link:
 * https://github.com/comely-io/translator/blob/master/LICENSE
 */

declare(strict_types=1);

namespace Comely\Translator;

use Comely\Filesystem\Directory;
use Comely\Translator\Cache\CacheDirectory;
use Comely\Translator\Exception\TranslatorException;
use Comely\Translator\Languages\Language;

/**
 * Class Translator
 * @package Comely\Translator
 * @property-read Directory $directory
 * @property-read CacheDirectory $cachingDirectory
 */
class Translator
{
    /** string Version (Major.Minor.Release-Suffix) */
    public const VERSION = "2.0.0";
    /** int Version (Major * 10000 + Minor * 100 + Release) */
    public const VERSION_ID = 20000;

    /** @var self |null */
    private static ?self $instance = null;

    /** @var Directory */
    private Directory $directory;
    /** @var null|CacheDirectory */
    private ?CacheDirectory $cache = null;
    /** @var Loader */
    private Loader $loader;
    /** @var Languages */
    private Languages $languages;
    /** @var null|string */
    private ?string $current = null;
    /** @var null|string */
    private ?string $fallback = null;

    /**
     * @return Translator
     * @throws TranslatorException
     */
    public static function getInstance(): self
    {
        if (!self::$instance) {
            throw new TranslatorException('Translator instance has not been created');
        }

        return self::$instance;
    }

    /**
     * @param Directory $translationsDirectory
     * @return Translator
     * @throws TranslatorException
     */
    public static function createInstance(Directory $translationsDirectory): self
    {
        if (self::$instance) {
            throw new TranslatorException('Translator instance already exists');
        }

        self::$instance = new self($translationsDirectory);
        return self::$instance;
    }

    /**
     * Translator constructor.
     * @param Directory $translationsDirectory
     * @throws TranslatorException
     */
    private function __construct(Directory $translationsDirectory)
    {
        if (!$translationsDirectory->permissions()->readable()) {
            throw new TranslatorException('Translations directory is not readable');
        }

        $this->directory = $translationsDirectory;
        $this->loader = new Loader();
        $this->languages = new Languages($this);
    }

    /**
     * @param string $prop
     * @return Directory|CacheDirectory|null
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "directory":
                return $this->directory;
            case "cachingDirectory":
                return $this->cache;
        }

        throw new \DomainException('Cannot get value of inaccessible property');
    }

    /**
     * @return Loader
     */
    public function load(): Loader
    {
        return $this->loader;
    }

    /**
     * @return Languages
     */
    public function languages(): Languages
    {
        return $this->languages;
    }

    /**
     * @return Translator
     */
    public function flush(): self
    {
        $this->loader->reset();
        $this->languages->reset();
        return $this;
    }

    /**
     * @param Directory $dir
     * @return Translator
     * @throws Exception\TranslatorCacheException
     */
    public function cachingDirectory(Directory $dir): self
    {
        $this->cache = new CacheDirectory($dir);
        return $this;
    }

    /**
     * @param string $name
     * @return Translator
     */
    public function language(string $name): self
    {
        $this->current = Language::isValidLanguageName($name);
        return $this;
    }

    /**
     * @param string $name
     * @return Translator
     */
    public function fallback(string $name): self
    {
        $this->fallback = Language::isValidLanguageName($name);
        return $this;
    }

    /**
     * @param string|null $lang
     * @return array
     * @throws Exception\LanguageException
     * @throws \Comely\Yaml\Exception\ParserException
     */
    public function getAll(?string $lang = null): array
    {
        $currentLang = $lang ?? $this->current;
        if (!$currentLang) {
            throw new \RuntimeException('No current language has been set');
        }

        return $this->languages->get($currentLang)->getAll();
    }

    /**
     * @param string $key
     * @param string|null $lang
     * @return string|null
     */
    public function translate(string $key, ?string $lang = null): ?string
    {
        if (!preg_match('/^[\w\-]+(\.[\w\-]+)*$/', $key)) {
            throw new \InvalidArgumentException('Invalid translation key');
        }

        $currentLang = $lang ?? $this->current;
        if (!$currentLang) {
            throw new \RuntimeException('No current language has been set');
        }

        try {
            $language = $this->languages->get($currentLang);
            $translated = $language->translate($key);
            if ($translated) {
                return $translated;
            }
        } catch (\Exception $e) {
            trigger_error(
                sprintf('Translation [%s] error: [%s][#%s] %s', $this->current, get_class($e), $e->getCode(), $e->getMessage()),
                E_USER_WARNING
            );
        }

        if ($this->fallback && $this->fallback !== $this->current) {
            try {
                $fallback = $this->languages->get($this->fallback);
                return $fallback->translate($key);
            } catch (\Exception $e) {
                trigger_error(
                    sprintf('Translation [%s] error: [%s][#%s] %s', $this->fallback, get_class($e), $e->getCode(), $e->getMessage()),
                    E_USER_WARNING
                );
            }
        }

        return null;
    }
}
