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

/**
 * Class Loader
 * @package Comely\Translator
 * @property-read array $selected
 * @property-read string $cacheId
 */
class Loader
{
    /** @var array */
    private $selection;

    /**
     * Loader constructor.
     */
    public function __construct()
    {
        $this->selection = [];
    }

    /**
     * @param string $prop
     * @return array|string
     */
    public function __get(string $prop)
    {
        switch ($prop) {
            case "selected":
                return array_keys($this->selection);
            case "cacheId":
                return implode("", array_values($this->selection));
        }

        throw new \DomainException('Cannot get value of inaccessible property');
    }

    /**
     * @return Loader
     */
    public function reset(): self
    {
        $this->selection = [];
        return $this;
    }

    /**
     * @return Loader
     */
    public function dictionary(): self
    {
        $this->selection["dictionary"] = "dkn";
        return $this;
    }

    /**
     * @return Loader
     */
    public function messages(): self
    {
        $this->selection["messages"] = "msg";
        return $this;
    }

    /**
     * @return Loader
     */
    public function sitemap(): self
    {
        $this->selection["sitemap"] = "smp";
        return $this;
    }

    /**
     * @return Loader
     */
    public function misc(): self
    {
        $this->selection["misc"] = "msc";
        return $this;
    }
}