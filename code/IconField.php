<?php

namespace PlasticStudio\IconField;

use DirectoryIterator;
use SilverStripe\Core\Path;
use SilverStripe\Assets\Folder;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Forms\OptionsetField;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

class IconField extends OptionsetField
{
    private static $folder_name;

    /**
     * Construct the field
     *
     * @param string $name
     * @param null|string $title
     * @param string $sourceFolder (legacy arg, for backwards-compatibility)
     *
     * @return array icons to provide as source array for the field
     **/
    public function __construct($name, $title = null, $sourceFolder = null)
    {
        // if ($sourceFolder) {
        //     // TODO: set deprecation notice
        //     // eg, "IconField no longer accepts Source Folder as a third parameter. Please use IconField->setFolderName() instead"
        //     $this->setFolderName($sourceFolder);
        // } else {
        //     // check default folder
        //     $exists = $this->checkForIconFolder($folderName);

        //     // set default folder



        // }

        parent::__construct($name, $title, []);
        
        $this->setSourceIcons();
    }

    public function requireDefaultRecords()
	{
        parent::requireDefaultRecords();
            
        $iconsFolder = Folder::find_or_make($this->getFolderName());
        $iconsFolder->publishFile();
        
    }

    // public function checkForIconFolder($folderName) {

    //     $folder = Folder::get()->filter('Name', $folderName)->first();

    //     if (!file_exists($folder)) {
    //         $folder = Folder::find_or_make($iconsFolder->Filename . '/' . $newFolder);
    //         $folder->ParentID = $iconsFolder->ID;
    //         $folder->write();
    //         $folder->publishFile();

    //         $folderPath = Path::join(
    //             ASSETS_PATH,
    //             $iconsFolder->Name,
    //             $folder->Name
    //         );

    //         if (!file_exists($folderPath)) {
    //             return false;
    //         } else {
    //             return true;
    //         }
    //     }
    // }

    // public function createIconFolder($newFolder) {

    //     $iconsFolder = Folder::find_or_make('Icons');
	// 	$iconsFolder->publishFile();

    //     if ($newFolder == 'Icons') {
    //         return '/assets/' . $iconsFolder->Name;
    //     } else {            

    //         $folder = Folder::find_or_make($iconsFolder->Filename . '/' . $newFolder);
    //         $folder->ParentID = $iconsFolder->ID;
    //         $folder->write();
    //         $folder->publishFile();

    //         $folderPath = Path::join(
    //             ASSETS_PATH,
    //             $iconsFolder->Name,
    //             $folder->Name
    //         );
            
    //         if (!file_exists($folderPath)) {
    //             mkdir($folderPath);
    //         }

    //         return '/assets/' . $iconsFolder->Name . '/' . $newFolder;
        
    //     }
    // }

    /**
     * Gets the icons folder name
     *
     * @return string
     */
    public function getFolderName()
    {
        if (is_null(self::$folder_name)) {
            self::$folder_name = Config::inst()->get(IconField::class, 'icons_folder_name');
        }
        return self::$folder_name;
    }

    // /**
    //  * Gets the icons folder name
    //  *
    //  * @return string
    //  */
    // public function getFolderName()
    // {
    //     if (is_null(self::$folder_name)) {
    //         self::$folder_name = Config::inst()->get(IconField::class, 'icons_directory');
    //     }
    //     return self::$folder_name;
    // }

    public function setFolderName($folder_name)
    {
        self::$folder_name = $folder_name;
        return $this;
    }

    public function setSourceIcons()
    {
        $icons = [];
        $extensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'];
        $relative_folder_path = $this->getFolderName();
        $absolute_folder_path = $this->getAbsolutePathFromRelative($relative_folder_path);


        // Scan each directory for files
        if (file_exists($absolute_folder_path)) {
            $directory = new DirectoryIterator($absolute_folder_path);
            foreach ($directory as $fileinfo) {
                if ($fileinfo->isFile()) {
                    $extension = strtolower(pathinfo($fileinfo->getFilename(), PATHINFO_EXTENSION));

                    // Only add to our available icons if it's an extension we're after
                    if (in_array($extension, $extensions)) {
                        $value = Path::join($relative_folder_path, $fileinfo->getFilename());
                        $title = $fileinfo->getFilename();
                        $icons[$value] = $title;
                    }
                }
            }
        }

        $this->source = $icons;
        return $this;
    }

    /**
     * Generate absolute path from relative path
     * (ie, prepend publicFolder or baseFolder path)
     * @param string relative path
     *
     * @return string absolute path
     */
    public function getAbsolutePathFromRelative($relative_path)
    {
        return Path::join(
            (Director::publicDir() ? Director::publicFolder() : Director::baseFolder()),
            ModuleResourceLoader::singleton()->resolveURL($relative_path)
        );
    }

    /**
     * Generate full relative path from partial relative path.
     * Uses ModuleResourceLoader->resolveURL to handle addition of _resources dir etc
     * For example, the icon path is stored in the db as 'app/client/assets/icons/default/icon.svg'
     * but we need the full relative path to render the icon in the field template: '/_resources/app/client/assets/icons/default/icon.svg'
     * @param string partial relative path
     *
     * @return string full relative path
     */

    public function getFullRelativePath($path)
    {
        return ModuleResourceLoader::singleton()->resolveURL($path);
    }

    /**
     * Build the field
     *
     * @return HTML
     **/
    public function Field($properties = [])
    {
        Requirements::css('plasticstudio/iconfield:css/IconField.css');
        $source = $this->getSource();
        $options = [];

        // Add a clear option
        $options[] = ArrayData::create([
            'ID' => 'none',
            'Name' => $this->name,
            'Value' => '',
            'Title' => '',
            'isChecked' => (!$this->value || $this->value == '')
        ]);

        if ($source) {
            foreach ($source as $value => $title) {
                $itemID = $this->ID() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $value);
                $options[] = ArrayData::create([
                    'ID' => $itemID,
                    'Name' => $this->name,
                    'Value' => $value,
                    'Title' => $title,
                    'isChecked' => $value == $this->value
                ]);
            }
        }

        $properties = array_merge($properties, [
            'Options' => ArrayList::create($options)
        ]);

        $this->setTemplate('IconField');

        return FormField::Field($properties);
    }

    /**
     * Handle extra classes
     **/
    public function extraClass()
    {
        $classes = ['field', 'IconField', parent::extraClass()];

        if (($key = array_search('icon', $classes)) !== false) {
            unset($classes[$key]);
        }

        return implode(' ', $classes);
    }
}
