# OpenEuropa Distribution

This distribution requires all the OpenEuropa modules and provides multiple
profiles to install a subset or all those modules. For more information on
what these modules provide, please refer to the OpenEuropa documentation on the
components:

 - https://github.com/openeuropa/documentation/blob/master/docs/openeuropa-components.md#openeuropa-components

## Installation

### Prerequisites

Because of 2 known issues in OpenEuropa modules before you require this profile
package, you need to

<blockquote>
<details><p><summary>1. make sure the require section of your composer.json contains the following:</summary>

```json
{
    "require": {
        "easyrdf/easyrdf": "0.10.0-alpha.1 as 0.9.1"
    }
}
```
</p></details>

<details><p><summary>2. make sure the extra section of your composer.json contains the following:</summary>

```json
{
    "extra": {
        "artifacts": {
            "openeuropa/oe_theme": {
                "dist": {
                    "url": "https://github.com/{name}/releases/download/{pretty-version}/{project-name}-{pretty-version}.tar.gz",
                    "type": "tar"
                }
            }
        }
    }
}
```
</p></details>

These issues have been reported in the OpenEuropa GitHub issue queue. If you
feel like helping in solving these you are more than welcome to:

 - openeuropa/oe_content: https://github.com/openeuropa/oe_content/issues/158
 - openeuropa/oe_theme: https://github.com/openeuropa/oe_theme/issues/424

### Composer command

After you have added the blocks of json into your projects composer.json you can
run the following command:

```bash
composer require verbruggenalex/oesd
```