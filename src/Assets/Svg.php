<?php

declare(strict_types=1);

namespace WeDevelop\SvgImage\Assets;

use enshrined\svgSanitize\Sanitizer;
use SilverStripe\Assets\File;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SVG\SVG as SVGParser;

/**
 * Image type that represends an svg file.
 */
class Svg extends Image
{
    /** @config */
    private static string $singular_name = "SVG";

    /** @config */
    private static string $plural_name = "SVGs";

    /** @config */
    private static bool $lazy_loading_enabled = false;

    private ?SVGParser $svg = null;

    /**
     * @param array<mixed> $record
     * @param bool|int $isSingleton
     * @param array<string, string> $queryParams
     */
    public function __construct($record = null, $isSingleton = false, array $queryParams = [])
    {
        parent::__construct($record, $isSingleton, $queryParams);

        if ($this->File->exists()) {
            $this->svg = SVGParser::fromString($this->File->getString());
        }
    }

    public function onBeforeWrite(): void
    {
        $svgSanitiser = new Sanitizer();
        $this->File->setFromString($svgSanitiser->sanitize($this->File->getString()), $this->File->getFilename());
        parent::onBeforeWrite();
    }

    public function getTag(): string
    {
        return $this->File->exists() ? $this->File->getString() : '';
    }

    public function getWidth(): int
    {
        return $this->svg ? intval($this->svg->getDocument()->getWidth()) : 0;
    }

    public function getHeight(): int
    {
        return $this->svg ? intval($this->svg->getDocument()->getHeight()) : 0;
    }

    /**
     * At the moment there is no SVG image manipulation in this module.
     *
     * When a manipulation method is called just return the wrapped file, this way
     * the actual image is displayed everywhere a manipulated image is expected.
     *
     * SVGs can be manipulated through CSS if needed for now. If anyone feels like it
     * they are free to implement image manipluation though ;).
     */
    public function manipulate($variant, $callback): DBFile
    {
        return $this->File;
    }
}
