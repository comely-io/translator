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

namespace Comely\Translator\Cache;

use Comely\Filesystem\Directory;
use Comely\Filesystem\Exception\PathException;
use Comely\Filesystem\Exception\PathNotExistException;
use Comely\Filesystem\Exception\PathPermissionException;
use Comely\Translator\Exception\TranslatorCacheException;
use Comely\Translator\Languages\Language;

/**
 * Class CacheDirectory
 * @package Comely\Translator\Cache
 */
class CacheDirectory
{
    /** @var Directory */
    private Directory $dir;

    /**
     * CacheDirectory constructor.
     * @param Directory $directory
     * @throws TranslatorCacheException
     */
    public function __construct(Directory $directory)
    {
        if (!$directory->permissions()->readable()) {
            throw new TranslatorCacheException('Translations cache directory is not readable');
        } elseif (!$directory->permissions()->writable()) {
            throw new TranslatorCacheException('Translations cache directory is not writable');
        }

        $this->dir = $directory;
    }

    /**
     * @param string $lang
     * @param string $group
     * @return Language|null
     * @throws TranslatorCacheException
     */
    public function get(string $lang, string $group): ?Language
    {
        $cachedFilename = sprintf('lang.%s.%s.php.cache', $lang, $group);

        try {
            $cachedFile = $this->dir->file($cachedFilename);
            $cachedFileBytes = $cachedFile->read();
        } catch (PathNotExistException) {
            return null;
        } catch (PathPermissionException) {
            throw new TranslatorCacheException(sprintf('Cached language file "%s" is not readable', $cachedFilename));
        } catch (PathException) {
            throw new TranslatorCacheException(sprintf('Failed to load cached language file "%s"', $cachedFilename));
        }

        try {
            $language = unserialize($cachedFileBytes, [
                "allowed_classes" => [
                    'Comely\Translator\Languages\Language'
                ]
            ]);
        } catch (\Exception) {
        }

        if (isset($language) && $language instanceof Language) {
            return $language;
        }

        // Attempt to delete file
        try {
            $this->dir->delete($cachedFilename);
        } catch (PathException) {
            trigger_error(
                sprintf('Failed to delete invalid cached language file "%s"', $cachedFilename),
                E_USER_WARNING
            );
        }

        throw new TranslatorCacheException(
            sprintf('Cached language file "%s" is incomplete or corrupted', $cachedFilename)
        );
    }

    /**
     * @param Language $lang
     * @throws TranslatorCacheException
     */
    public function store(Language $lang): void
    {
        $cacheFilename = sprintf('lang.%s.%s.php.cache', $lang->name(), $lang->group());

        try {
            $this->dir->write($cacheFilename, serialize($lang), false, true);
        } catch (PathException) {
            throw new TranslatorCacheException(
                sprintf('Failed to write compiled language cache file "%s"', $cacheFilename)
            );
        }
    }
}
