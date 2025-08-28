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
    protected static string $commandName = 'IconFieldPathMigrator';

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $request = Injector::inst()->get(HTTPRequest::class);
        $vars = $request->getVars();

        if (!isset($vars['classname']) || !isset($vars['field'])) {
            echo 'Pass both class and field in the query string, eg ?classname=Skeletor\DataObjects\SummaryPanel&field=SVGIcon' . '<br>';
            echo 'If new folder is not \'SiteIcons\', pass new-path in the query string, eg &new-path=NewFolder' . '<br>';
            echo 'Classname needs to include namespacing' . '<br>';
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
            die("Class $classname does not exist. Make sure to add the namespacing.");
        }

        $objects = $classname::get();
        $schema = DataObject::getSchema();
        if (!$schema->classHasTable($classname)) {
            die("Class $classname does not have a table.");
        }
        $tableName = Convert::raw2sql($schema->tableName($classname));// Sanitize column name
        $iconCol = Convert::raw2sql($iconField); // Sanitize column name


        if ($objects && $tableName) {
            foreach ($objects as $object) {
                // if there is an icon
                if ($originIconPath = $object->$iconField) {
                    $originIconName = basename($originIconPath);

                    echo $object->Title . '<br>';
                    echo 'Origin Icon Path: ' . $originIconPath . '<br>';
                    echo 'Origin Icon Name: ' . $originIconName . '<br>';

                    $newIconPath = $folderPath . '/' . $originIconName;
                    echo 'New Icon Path: ' . $newIconPath . '<br>';

                    DB::prepared_query("UPDATE {$tableName} SET {$iconCol} = ? WHERE ID = ?", [$newIconPath, $object->ID]);
                    echo $tableName.' updated' . '<br>';

                    if ($object->hasExtension(Versioned::class)) {
                        $tableNameVersioned = $tableName.'_Versions';
                        DB::prepared_query("UPDATE {$tableNameVersioned} SET {$iconCol} = ? WHERE RecordID = ?", [$newIconPath, $object->ID]);
                        echo $tableNameVersioned . '<br>';

                        if ($object->isPublished()) {
                            $tableNameLive = $tableName.'_Live';
                            DB::prepared_query("UPDATE {$tableNameLive} SET {$iconCol} = ? WHERE ID = ?", [$newIconPath, $object->ID]);
                            echo $tableNameLive . '<br>';
                        }
                    }


                    echo 'panel icon updated' . '<br>';
                } else {
                    echo $object->Title . '<br>no icon - no update' . '<br>';
                }

                echo '<br />-------<br />';
            }
        } else {
            echo 'No objects found';
        }
        return Command::SUCCESS;
    }
}