## Download the extension
##### Magento Marketplace
https://marketplace.magento.com/epartment-official-idin-magento-extension.html
##### Magento Connect
https://www.magentocommerce.com/magento-connect/official-idin-magento-extension.html

## Installation through Magento Marketplace
1. Download the `tgz` file through the Magento Marketplace. Then please follow the steps for manual installation below.

## Install / update through Magento Connect
1. Paste the extension key `http://connect20.magentocommerce.com/community/official-idin-magento-extension` into the connect manager
2. Click on install

## Manual installation / update
1. Download the latest version of this extension
2. Unzip the files in a local folder
3. Put the folders `app`, `lib` and `skin` in the root of the Magento instance
4. When needed, empty the Magento cache in order to execute installation/upgrade scripts.

## Uninstall the extesnsion
In order to remove the extension without leaving any traces, we advice you to follow the steps below.

### Deactivation
1. Go to System --> Congiguration --> Services --> CM iDIN --> Common Settings
2. Set `Active` to `No`

### Remove extension files

!!! note "Deactivation"
    By first deactiving the extension, any installed product attributes will automatically be disabled.

1. Remove the following files and folders

`app/code/community/CMGroep/Idin`<br/>
`app/design/adminhtml/base/default/layout/cmgroep_idin.xml`<br/>
`app/design/adminhtml/base/default/template/cm/idin`<br/>
`app/design/frontend/base/default/layout/cmgroep_idin.xml`<br/>
`app/design/frontend/base/default/template/cmgroep/idin`<br/>
`app/etc/modules/CMGroep_Idin.xml`<br/>
`app/locale/en_US/template/email/cmgroep/idin`<br/>
`app/locale/nl_NL/CMGroep_Idin.csv`<br/>
`lib/CMGroep/Idin`<br/>
`skin/adminhtml/base/default/cm/idin`<br/>
`skin/frontend/base/default/cm/idin`

