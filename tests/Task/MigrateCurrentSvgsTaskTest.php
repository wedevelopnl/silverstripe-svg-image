<?php

declare(strict_types=1);

namespace WeDevelop\SvgImage\Tests\Task;

use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use SilverStripe\Assets\File;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Input\ArrayInput;
use WeDevelop\SvgImage\Assets\Svg;
use WeDevelop\SvgImage\Task\MigrateCurrentSvgsTask;

#[CoversClass(MigrateCurrentSvgsTask::class)]
class MigrateCurrentSvgsTaskTest extends SapphireTest
{
    protected static $fixture_file = '../fixtures/MigrateCurrentSvgsTaskTest.yml';

    public function testRunFlipsClassNameForSvgFiles(): void
    {
        $iconA = $this->objFromFixture(File::class, 'iconA');
        $iconB = $this->objFromFixture(File::class, 'iconB');
        $this->assertSame(File::class, $iconA->ClassName);
        $this->assertSame(File::class, $iconB->ClassName);

        $this->runTask();

        $this->assertSame(Svg::class, File::get()->byID($iconA->ID)?->ClassName);
        $this->assertSame(Svg::class, File::get()->byID($iconB->ID)?->ClassName);
    }

    public function testRunDoesNotChangeNonSvgFiles(): void
    {
        $photo = $this->objFromFixture(File::class, 'photo');
        $document = $this->objFromFixture(File::class, 'document');

        $this->runTask();

        $this->assertSame(File::class, File::get()->byID($photo->ID)?->ClassName);
        $this->assertSame(File::class, File::get()->byID($document->ID)?->ClassName);
    }

    private function runTask(): void
    {
        $task = new MigrateCurrentSvgsTask();
        $input = new ArrayInput([]);
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI);

        // execute() is the abstract method subclasses implement; run() does extra
        // bookkeeping we don't need in unit tests, so invoke execute() directly.
        $method = new ReflectionMethod($task, 'execute');
        $method->invoke($task, $input, $output);
    }
}
