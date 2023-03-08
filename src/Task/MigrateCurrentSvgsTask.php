<?php

declare(strict_types=1);

use SilverStripe\Dev\BuildTask;
use SilverStripe\ORM\Queries\SQLUpdate;
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
    protected $title = 'Migrate svg files into the svg image file type';

    /** @config */
    private static string $segment = 'migrate-svg-files';

    protected $description = 'Migrates svgs stored as the general file type into the new svg image type';

    public function run($request): void
    {
        SQLUpdate::create('file', ['ClassName' => Svg::class], ['Name LIKE ?' => '%.svg'])->execute();
    }
}
