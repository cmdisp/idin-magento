!!! tip "Please note"
    To make sure this installation of the extension will be correctly executed, we advice you to install this extension through Magento Connect.

## Install / update through Magento Connect
1. Paste the extension key `extension-key` into the connect manager
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

