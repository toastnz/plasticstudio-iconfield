# Install

`composer require plasticstudio/iconfield`

# Description

Provides a visual icon picker for content authors. Icon files are managable via the asset library.

![IconField](https://raw.githubusercontent.com/PlasticStudio/IconField/master/screenshot.jpg)

# Requirements

- SilverStripe 4 or 5

# Usage

- Import the required classes:

```
use PlasticStudio\IconField\Icon;
use PlasticStudio\IconField\IconField;
```

- Set your `$db` field to type `Icon` (eg `'PageIcon' => Icon::class`)
- `IconField::create($name, $title)`
  - `$name` is the database field as defined in your class
  - `$title` is the label for this field
- Add a folder containing icons to your project; icons in this folder will be used by the field as options which you can select. The default location of this folder (as defined in `_config/config.yml`) is `Icons`. You can override this global default in your project's own config like so:

```
PlasticStudio\IconField\IconField:
  icons_folder_name: SocialIcons
```

- Use your icon in templates as you would any other property (eg `$PageIcon`). If your icon is an SVG, the SVG image data will be injected into the template. To prevent this, you can call `$PageIcon.IMG` instead to enforce use of `<img>` tags.

- Add a default width and height to the config.yml file to output width/height attributes on image tags

```
default_width: "30"
default_height: "30"
```