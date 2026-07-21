<?php

declare(strict_types=1);

namespace WeDevelop\SvgImage\Assets;

use Exception;
use Override;
use enshrined\svgSanitize\Sanitizer;
use Psr\Log\LoggerInterface;
use SilverStripe\Assets\Image;
use SilverStripe\Assets\Storage\DBFile;
use SVG\SVG as SVGParser;

/**
 * Image type that represends an svg file.
 */
class Svg extends Image
{
    /** @config */
    private static string $table_name = 'WeDevelop_SvgImage_Svg';

    /** @config */
    private static string $singular_name = 'SVG';

    /** @config */
    private static string $plural_name = 'SVGs';

    /** @config */
    private static bool $lazy_loading_enabled = false;

    private ?SVGParser $svg = null;

    private bool $svgParsed = false;

    public LoggerInterface $logger;

    /**
     * @config
     * @var array<string, string>
     */
    private static array $dependencies = [
        'logger' => '%$' . LoggerInterface::class,
    ];

    #[Override]
    public function onBeforeWrite(): void
    {
        $filename = $this->File->getFilename();
        if ($filename !== null && $this->File->exists()) {
            $svgSanitiser = new Sanitizer();
            $this->File->setFromString($svgSanitiser->sanitize($this->File->getString()) ?: '', $filename);
        }

        parent::onBeforeWrite();
    }

    #[Override]
    public function getTag(): string
    {
        return $this->File->exists() ? $this->File->getString() : '';
    }

    #[Override]
    public function getWidth(): int
    {
        $this->parseSvg();

        return $this->svg instanceof SVGParser ? intval($this->svg->getDocument()->getWidth()) : 0;
    }

    #[Override]
    public function getHeight(): int
    {
        $this->parseSvg();

        return $this->svg instanceof SVGParser ? intval($this->svg->getDocument()->getHeight()) : 0;
    }

    // Deferred until first access so the framework has injected $logger via $dependencies
    // by the time we try to log a parse failure.
    private function parseSvg(): void
    {
        if ($this->svgParsed) {
            return;
        }

        $this->svgParsed = true;

        if (!$this->File->exists()) {
            return;
        }

        try {
            $this->svg = SVGParser::fromString($this->File->getString());
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
        }
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
    #[Override]
    public function manipulate(mixed $variant, mixed $callback): DBFile
    {
        return (clone $this->File)->setOriginal($this);
    }
}
