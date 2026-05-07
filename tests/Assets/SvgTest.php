<?php

declare(strict_types=1);

namespace WeDevelop\SvgImage\Tests\Assets;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use SilverStripe\Assets\Dev\TestAssetStore;
use SilverStripe\Assets\File;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Queries\SQLUpdate;
use WeDevelop\SvgImage\Assets\Svg;

#[CoversClass(Svg::class)]
class SvgTest extends SapphireTest
{
    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
        TestAssetStore::activate('SvgTest');
    }

    protected function tearDown(): void
    {
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testGetTagReturnsEmptyStringWhenFileDoesNotExist(): void
    {
        $svg = Svg::create();

        $this->assertSame('', $svg->getTag());
    }

    public function testGetWidthReturnsZeroWhenFileDoesNotExist(): void
    {
        $svg = Svg::create();

        $this->assertSame(0, $svg->getWidth());
    }

    public function testGetHeightReturnsZeroWhenFileDoesNotExist(): void
    {
        $svg = Svg::create();

        $this->assertSame(0, $svg->getHeight());
    }

    public function testGetTagReturnsFileContent(): void
    {
        $svg = $this->writeSvg($this->loadFixture('sample.svg'), 'tag.svg');

        // Re-load so the constructor runs against the persisted, sanitized content.
        $reloaded = Svg::get()->byID($svg->ID);

        $this->assertNotNull($reloaded);
        $this->assertStringContainsString('<svg', $reloaded->getTag());
        $this->assertStringContainsString('<rect', $reloaded->getTag());
    }

    public function testGetWidthIsParsedFromSvgContent(): void
    {
        $svg = $this->writeSvg($this->loadFixture('sample.svg'), 'width.svg');

        $reloaded = Svg::get()->byID($svg->ID);

        $this->assertNotNull($reloaded);
        $this->assertSame(100, $reloaded->getWidth());
    }

    public function testGetHeightIsParsedFromSvgContent(): void
    {
        $svg = $this->writeSvg($this->loadFixture('sample.svg'), 'height.svg');

        $reloaded = Svg::get()->byID($svg->ID);

        $this->assertNotNull($reloaded);
        $this->assertSame(50, $reloaded->getHeight());
    }

    public function testManipulateReturnsWrappedFileWithoutTransform(): void
    {
        $svg = $this->writeSvg($this->loadFixture('sample.svg'), 'manipulate.svg');

        $result = $svg->manipulate('any-variant', static fn (): mixed => null);

        $this->assertSame($svg->File, $result);
    }

    public function testOnBeforeWriteSanitizesUnsafeContent(): void
    {
        $svg = $this->writeSvg($this->loadFixture('malicious.svg'), 'malicious.svg');

        $tag = Svg::get()->byID($svg->ID)?->getTag() ?? '';

        $this->assertStringNotContainsString('<script', $tag);
        $this->assertStringNotContainsString('onclick', $tag);
        $this->assertStringNotContainsString('javascript:', $tag);
    }

    public function testLazyLoadingIsDisabledByDefault(): void
    {
        $this->assertFalse(Svg::config()->get('lazy_loading_enabled'));
    }

    public function testInvalidSvgContentIsLoggedAndDoesNotThrow(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('error')
            ->with($this->isType('string'));

        Injector::inst()->registerService($logger, LoggerInterface::class);

        // Write garbage via a regular File so the Svg sanitizer does NOT run on it.
        $file = File::create();
        $file->File->setFromString('this is not valid svg', 'broken.svg');
        $file->write();

        // Flip the ClassName so we can re-load the row as a Svg.
        SQLUpdate::create('"File"', ['"ClassName"' => Svg::class], ['"ID"' => $file->ID])->execute();
        DataObject::flush_and_destroy_cache();

        // Parsing is lazy; calling getWidth() forces it. Garbage content => exception caught and logged.
        // The @ suppresses warnings from meyfa/php-svg's SimpleXMLElement call on garbage input,
        // which fire before the exception we care about is thrown.
        $svg = Svg::get()->byID($file->ID);
        $this->assertNotNull($svg);
        $this->assertSame(0, @$svg->getWidth());
    }

    private function writeSvg(string $content, string $filename): Svg
    {
        $svg = Svg::create();
        $svg->File->setFromString($content, $filename);
        $svg->write();

        return $svg;
    }

    private function loadFixture(string $name): string
    {
        $path = __DIR__ . '/../fixtures/' . $name;
        $content = file_get_contents($path);

        if ($content === false) {
            self::fail("Could not load fixture: {$name}");
        }

        return $content;
    }
}
