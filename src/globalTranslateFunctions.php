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

namespace {

    use Comely\Translator\Exception\TranslatorException;
    use Comely\Translator\Translator;

    if (!function_exists("__")) {
        /**
         * Global translate function # 1
         * Retrieves a translation against $key
         *
         * @param string $key
         * @param null|string $lang If NULL, currently set Language instance will be used
         * @return null|string
         */
        function __(string $key, ?string $lang = null): ?string
        {
            try {
                return Translator::getInstance()->translate($key, $lang);
            } catch (TranslatorException $e) {
                throw new RuntimeException(
                    sprintf('[%s][%s] %s', get_class($e), $e->getCode(), $e->getMessage())
                );
            }
        }
    }

    if (!function_exists("__k")) {
        /**
         * Global translate function # 2
         * Retrieves translation, return $key is no translation was found
         *
         * @param string $key
         * @param null|string $lang If NULL, currently set Language instance will be used
         * @return string
         */
        function __k(string $key, ?string $lang = null): string
        {
            return __($key, $lang) ?? $key;
        }
    }

    if (!function_exists("__f")) {
        /**
         * Global translate function # 3
         * Retrieves translation, format using vsprintf
         *
         * @param string $key
         * @param array $args
         * @param null|string $lang
         * @return null|string
         */
        function __f(string $key, array $args, ?string $lang = null): ?string
        {
            $translation = __($key, $lang);
            if ($translation) {
                $formatted = vsprintf($translation, $args);
                if ($formatted) {
                    return $formatted;
                }
            }

            return null;
        }
    }
}