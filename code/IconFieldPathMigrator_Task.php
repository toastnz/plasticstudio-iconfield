<?php

use SilverStripe\ORM\DB;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\ClassInfo;
use SilverStripe\ORM\DataObject;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Versioned\Versioned;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\PolyExecution\PolyOutput;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

class IconFieldPathMigrator_BuildTask extends BuildTask
{
    /**
     * 1. Update IconField fields to use new folder path, eg `IconField::create('SocialIcon', 'Icon', 'SiteIcons')`
     * 1. Set up new folder in assets/SiteIcons in the CMS
     * 2. Copy the icons into the folder
     * 3. Publish the icon files
     * 4. Run this task - include params
     */

    protected string $title = 'Update icon file paths to assets folder';
    protected $enabled = true;

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $vars = $request->getVars();

        if (!isset($vars['classname']) || !isset($vars['field'])) {
            $output->writeln('Please pass classname and field in the query string.');
            $output->writeln('eg ?classname=Skeletor\DataObjects\SummaryPanel&field=SVGIcon');
            $output->writeln('Pass both class and field in the query string, eg ?classname=Skeletor\DataObjects\SummaryPanel&field=SVGIcon');
            $output->writeln('If new folder is not \'SiteIcons\', pass new-path in the query string, eg &new-path=NewFolder');
            $output->writeln('Classname needs to include namespacing');
            return Command::FAILURE;
        }

        $classname = $vars['classname'];
        $iconField = $vars['field'];

        // check for folder path
        if ( isset($vars['new-path']) ) {
            $folderPath = 'assets/' . $vars['new-path'];
        } else {
            $folderPath = 'assets/SiteIcons';
        }

        // check if site is namespaced
        if (!ClassInfo::exists($classname)) {
            $output->writeln("Class $classname does not exist. Make sure to add the namespacing.");
            return Command::FAILURE;
        }

        $objects = $classname::get();
        $schema = DataObject::getSchema();
        if (!$schema->classHasTable($classname)) {
            $output->writeln("Class $classname does not have a table.");
            return Command::FAILURE;
        }
        $tableName = Convert::raw2sql($schema->tableName($classname));// Sanitize column name
        $iconCol = Convert::raw2sql($iconField); // Sanitize column name


        if ($objects && $tableName) {
            foreach ($objects as $object) {
                // if there is an icon
                if ($originIconPath = $object->$iconField) {
                    $originIconName = basename($originIconPath);
                    $output->writeln('Updating icon for ' . $object->Title);
                    $output->writeln('Origin Icon Path: ' . $originIconPath);
                    $output->writeln('Origin Icon Name: ' . $originIconName);

                    $newIconPath = $folderPath . '/' . $originIconName;
                    $output->writeln('New Icon Path: ' . $newIconPath);

                    DB::prepared_query("UPDATE {$tableName} SET {$iconCol} = ? WHERE ID = ?", [$newIconPath, $object->ID]);
                    $output->writeln($tableName.' updated');    

                    if ($object->hasExtension(Versioned::class)) {
                        $tableNameVersioned = $tableName.'_Versions';
                        DB::prepared_query("UPDATE {$tableNameVersioned} SET {$iconCol} = ? WHERE RecordID = ?", [$newIconPath, $object->ID]);
                        $output->writeln($tableNameVersioned.' updated');

                        if ($object->isPublished()) {
                            $tableNameLive = $tableName.'_Live';
                            DB::prepared_query("UPDATE {$tableNameLive} SET {$iconCol} = ? WHERE ID = ?", [$newIconPath, $object->ID]);
                            $output->writeln($tableNameLive.' updated');
                        }
                    }


                    $output->writeln('panel icon updated');
                } else {
                    $output->writeln($object->Title . ' no icon - no update');
                }

                $output->writeln('-------');
            }
        } else {
            $output->writeln('No objects found');
        }
        return Command::SUCCESS;
    }
}