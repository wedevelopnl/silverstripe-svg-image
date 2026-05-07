<?php

declare(strict_types=1);

namespace WeDevelop\SvgImage\Task;

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLUpdate;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use WeDevelop\SvgImage\Assets\Svg;

/**
 * We've got some other modules (most notably `wedevelopnl/silverstripe-icon-manager`), however that module just
 * adds svgs as regular files and attempts to display them by outputting the raw data.
 *
 * This task migrates all `.svg` files in the database into the new asset filetype.
 *
 * @internal
 */
class MigrateCurrentSvgsTask extends BuildTask
{
    protected string $title = 'Migrate svg files into the svg image file type';

    protected static string $description = 'Migrates svgs stored as the general file type into the new svg image type';

    /** @config */
    protected static string $commandName = 'migrate-svg-files';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        SQLUpdate::create('"File"', ['"ClassName"' => Svg::class], ['"Name" LIKE ?' => '%.svg'])->execute();

        return Command::SUCCESS;
    }
}
