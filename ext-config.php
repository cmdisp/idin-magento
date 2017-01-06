<?php
return array(

//The base_dir and archive_file path are combined to point to your tar archive
//The basic idea is a separate process builds the tar file, then this finds it
'base_dir'               => '/builds/epartment',
'archive_files'          => 'CMGroep_Idin.tar',

//The Magento Connect extension name.  Must be unique on Magento Connect
//Has no relation to your code module name.  Will be the Connect extension name
'extension_name'         => 'CMGroep_Idin',

//Your extension version.  By default, if you're creating an extension from a 
//single Magento module, the tar-to-connect script will look to make sure this
//matches the module version.  You can skip this check by setting the 
//skip_version_compare value to true
'extension_version'      => '1.0.3',
'skip_version_compare'   => true,

//You can also have the package script use the version in the module you 
//are packaging with. 
'auto_detect_version'   => true,

//Where on your local system you'd like to build the files to
'path_output'            => '/builds/epartment/package',

//Magento Connect license value. 
'stability'              => 'stable',

//Magento Connect license value 
'license'                => 'MIT',

//Magento Connect channel value.  This should almost always (always?) be community
'channel'                => 'community',

//Magento Connect information fields.
'summary'                => 'Official iDIN extension',
'description'            => 'This extension provides iDIN functionality.',
'notes'                  => 'Release 1.0',

//Magento Connect author information. If author_email is foo@example.com, script will
//prompt you for the correct name.  Should match your http://www.magentocommerce.com/
//login email address
'author_name'            => 'Epartment Ecommerce',
'author_user'            => 'epartment',
'author_email'           => 'support@epartment.nl',

//PHP min/max fields for Connect.  I don't know if anyone uses these, but you should
//probably check that they're accurate
'php_min'                => '5.4.0',
'php_max'                => '7.1.0'
);
