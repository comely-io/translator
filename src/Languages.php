<?php
/**
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

use Comely\Translator\Exception\TranslatorCacheException;
use Comely\Translator\Languages\Language;

/**
 * Class Languages
 * @package Comely\Translator
 */
class Languages
{
    /** @var Translator */
    private $translator;
    /** @var array */
    private $langs;

    /**
     * Languages constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
        $this->langs = [];
    }

    /**
     * @return Languages
     */
    public function reset(): self
    {
        $this->langs = [];
        return $this;
    }

    /**
     * @param string $name
     * @return Language
     * @throws Exception\LanguageException
     * @throws \Comely\Yaml\Exception\ParserException
     */
    public function get(string $name): Language
    {
        $name = Language::isValidLanguageName($name);

        // Check in memory
        if (isset($this->langs[$name])) {
            return $this->langs[$name];
        }

        // Check cached compiled language file
        $lang = $this->cached($name);
        if ($lang) {
            $this->langs[$name] = $lang;
            return $lang;
        }

        // Compile fresh
        $lang = new Language($this->translator, $name);

        // Store in cache?
        $cacheDirectory = $this->translator->cachingDirectory;
        if ($cacheDirectory) {
            try {
                $cacheDirectory->store($lang);
            } catch (TranslatorCacheException $e) {
                trigger_error($e->getMessage(), E_USER_WARNING);
            }
        }

        $this->langs[$name] = $lang;
        return $lang;
    }

    /**
     * @param string $name
     * @return Language|null
     */
    private function cached(string $name): ?Language
    {
        $cacheDirectory = $this->translator->cachingDirectory;
        if (!$cacheDirectory) {
            return null;
        }

        try {
            return $cacheDirectory->get($name, $this->translator->load()->cacheId);
        } catch (TranslatorCacheException $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        return null;
    }
}