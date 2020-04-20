# Asset Purge plugin for Craft CMS 3.x

Provides an interface to view unused assets.

![Screenshot](resources/img/plugin-logo.png)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

To install the plugin, follow these instructions.

1. Open your terminal and go to your Craft project:

        cd /path/to/project

2. Then tell Composer to load the plugin:

        composer require fvaldes33/asset-purge

3. In the Control Panel, go to Settings → Plugins and click the “Install” button for Asset Purge.

## Asset Purge Overview

This plugin extends the Asset element and provides an element index view to review all unused assets. From this view, you can safely delete assets and clean up your asset folders. One big hurdle was making sure that all assets inside Redactor field types were accounted for.

#### Tested On:
* Asset Field
* Redactor Field
* Matrix Field
  - Including blocks with asset fields
  - relationship fields
  - redactor fields
* Globals
* Users

#### Plugin Compatibility:
| Plugin | Entries Field | Categories Field | Redactor Field |
|---|---|---|---|
| Supertable | ✅ | ✅ | ✅ |
| Neo | ✅ | ✅ | ✅ |
| Linkit (Roadmap) | ❌ | ❌ | ❌ |


## Configuring Asset Purge

No configuration required

## Using Asset Purge

* Navigate to the Asset Purge CP page
* Review assets and select items to delete
* Use native craft delete action to remove unused assets

## Asset Purge Roadmap

* Release it
* Add LinkIt plugin support

Brought to you by [fvaldes33](https://github.com/fvaldes33)
