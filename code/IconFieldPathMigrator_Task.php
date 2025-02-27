<?php

use SilverStripe\Dev\BuildTask;

class IconFieldPathMigrator_BuildTask extends BuildTask
{
    /**
     * 1. Set up new folder in assets/Icons for the field
     * 2. Copy the icons into the folder
     * 3. Publish the icon files
     * 4. Run this task
     */

    protected $title = 'Update icon file paths to assets folder';
    protected $enabled = true;

    public function run($request)
    {
        $vars = $request->getVars();

        if (!isset($vars['class']) || !isset($vars['field'])) {
            echo 'Pass both class and field in the query string, eg ?class=SummaryPanel&field=SVGIcon' . '<br>';
            echo 'If new folder is not \'SiteIcons\', pass new-path in the query string, eg &new-path=NewFolder' . '<br>';
            return;
        }

        $class = $vars['class'];
        $iconField = $vars['field'];

        // check for folder path
        if ( isset($vars['new-path']) ) {
            $folderPath = 'assets/' . $vars['new-path'];
        } else {
            $folderPath = 'assets/SiteIcons';
        }

        $objects = $class::get();

        if ($objects) {
            foreach ($objects as $object) {
                // if there is an icon
                if ($originIconPath = $object->$iconField) {
                    $originIconName = basename($originIconPath);

                    echo $object->Title . '<br>';
                    echo 'Origin Icon Path: ' . $originIconPath . '<br>';
                    echo 'Origin Icon Name: ' . $originIconName . '<br>';

                    $newIconPath = $folderPath . '/' . $originIconName;
                    echo 'New Icon Path: ' . $newIconPath . '<br>';

                    $object->$iconField = $newIconPath;
                    $object->write();

                    echo 'panel icon updated' . '<br>';
                } else {
                    echo $object->Title . '<br>no icon - no update' . '<br>';
                }

                echo '<br />-------<br />';
            }
        }
    }
}