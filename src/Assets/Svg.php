<?php

declare(strict_types=1);

namespace WeDevelop\SvgImage\Assets;

use enshrined\svgSanitize\Sanitizer;
use SilverStripe\Assets\Image;
use SVG\SVG as SVGParser;

/**
 * Represents a SVG image
 */
class Svg extends Image
{
    /**
     * @config
     * @var string
     */
    private static $singular_name = "SVG";

    /**
     * @config
     * @var string
     */
    private static $plural_name = "SVGs";

    /**
     * @config
     * @var bool
     */
    private static $lazy_loading_enabled = false;

    private ?SVGParser $svg = null;

    public function __construct($record = null, $isSingleton = false, $queryParams = [])
    {
        parent::__construct($record, $isSingleton, $queryParams);

        if ($this->File && $this->File->getString() !== null) {
            $this->svg = SVGParser::fromString($this->File->getString());
        }
    }

    public function onBeforeWrite()
    {
        $svgSanitiser = new Sanitizer();
        $this->File->setFromString($svgSanitiser->sanitize($this->File->getString()), $this->File->getFilename());
        parent::onBeforeWrite();
    }

    public function getTag()
    {
        return $this->File ? $this->File->getString() : '';
    }

    /**
     * @return int|string
     */
    public function getWidth()
    {
        if ($this->svg) {
            return $this->svg->getDocument()->getWidth();
        }
        return 0;
    }

    /**
     * @return int|string
     */
    public function getHeight()
    {
        if ($this->svg) {
            return $this->svg->getDocument()->getHeight();
        }
        return 0;
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
    public function manipulate($variant, $callback)
    {
        return $this->File;
    }
}
