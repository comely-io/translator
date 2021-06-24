<?php
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

namespace Comely\Translator\Languages;

use Comely\Filesystem\Exception\PathException;
use Comely\Filesystem\Exception\PathNotExistException;
use Comely\Translator\Exception\LanguageException;
use Comely\Translator\Translator;
use Comely\Yaml\Yaml;

/**
 * Class Language
 * @package Comely\Translator\Languages
 */
class Language
{
    /** @var string */
    private string $name;
    /** @var string */
    private string $group;
    /** @var array */
    private array $translations = [];

    /**
     * Language constructor.
     * @param Translator $translator
     * @param string $lang
     * @throws LanguageException
     * @throws \Comely\Yaml\Exception\ParserException
     */
    public function __construct(Translator $translator, string $lang)
    {
        $this->name = self::isValidLanguageName($lang);
        $this->group = $translator->load()->cacheId;

        // Load language directory
        try {
            $langDirectory = $translator->directory->dir($this->name, false);
        } catch (PathNotExistException) {
            throw new LanguageException(sprintf('Language directory "%s" not found', $this->name));
        } catch (PathException) {
            throw new LanguageException(sprintf('Failed to load language "%s" directory', $this->name));
        }

        if (!$langDirectory->permissions()->readable()) {
            throw new LanguageException(sprintf('Language directory "%s" is not readable', $this->name));
        }

        // Load translations
        $count = 0;
        foreach ($translator->load()->selected as $file) {
            $parse = Yaml::Parse($langDirectory->suffix($file . ".yml"))
                ->options(eolChar: "\n", evaluateBooleans: false, evaluateNulls: true, mbEncoding: "UTF-8")
                ->generate();

            $this->feed($parse);
            $count++;
            unset($parse);
        }

        if (!$count) {
            throw new LanguageException('No translation files were loaded');
        }
    }

    /**
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function group(): string
    {
        return $this->group;
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function translate(string $key): ?string
    {
        $key = strtolower($key);
        if (!preg_match('/[\w\-.]+/', $key)) {
            throw new \InvalidArgumentException('Invalid translation key');
        }

        return $this->translations[$key] ?? null;
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->translations;
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [
            "name" => $this->name,
            "group" => $this->group,
            "translations" => $this->translations
        ];
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->name = $data["name"];
        $this->group = $data["group"];
        $this->translations = $data["translations"];
    }

    /**
     * @param array $translations
     * @param string|null $parent
     */
    private function feed(array $translations, ?string $parent = null): void
    {
        foreach ($translations as $key => $value) {
            // Validate key
            $key = trim(strtolower(sprintf('%s.%s', $parent ?? "", $key)), ".-_"); // Trim special chars from start/end
            if (!preg_match('/^[a-z0-9.\-_]+$/', $key)) {
                $this->compileError(sprintf('Invalid translation key in parent "%s"', $parent ?? "~"));
                continue;
            }

            if (is_string($value)) {
                $this->translations[$key] = $value;
            } elseif (is_scalar($value)) {
                $this->translations[$key] = strval($value);
            } elseif (is_null($value)) {
                $this->translations[$key] = "";
            } elseif (is_array($value)) {
                $this->feed($value, $key);
            } else {
                $this->compileError(sprintf('Invalid translation value for key "%s"', $key));
            }
        }
    }

    /**
     * @param string $message
     */
    private function compileError(string $message): void
    {
        trigger_error(sprintf('Language [%s]: %s', $this->name, $message), E_USER_NOTICE);
    }

    /**
     * @param string $in
     * @return string
     */
    public static function isValidLanguageName(string $in): string
    {
        $out = strtolower($in);
        if (!preg_match('/^[a-z]+(-[a-z]{2})?$/', $out)) {
            throw new \InvalidArgumentException('Bad language name');
        }

        return $out;
    }
}
