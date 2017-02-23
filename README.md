Page Templates for TYPO3
========================

This extension provides the possibility to provide and easily use boilerplates for page creation.

Simple mode
-----------

The simple mode is enabled by default and can be disabled by the integrator within the extension manager. 
With this switch the Page Template module will be disabled and a new optin in the click menu is enabled. 
Also she must provide the UID of the storage folder of the page template. 
An editor now can create pages with content elements within this storage folder.


YAML mode
---------

Provides yaml based templates for TYPO3 pages. Allows an editor to select a page template for creating a new page.
Allows editing of page and content properties on creation.
After disabling the simple mode in the extension manager, a new module will be displayed.


### Page TSConfig:

| Page TSConfig | Default | Description |
| --------------|---------|-------------|
| mod.web_PagetemplatesTxPagetemplates.storagePath | | Path to the directory containing the YAML configuration files (EXT: syntax is supported) |


#### YAML:

Folder structure:

- Templates.yaml
  - Structure
    - example1.yaml
    - example2.yaml
    
    
Main Templates.yaml file contains the configuration for the available templates:

```yaml
    example1:
        name: "Example Template 1"
        previewImage: "EXT:pagetemplates/ext_icon.svg"
        description: "This is an example template for use in the templates extension."
    example2:
        name: "Example Template 2"
        previewImage: "EXT:pagetemplates/ext_icon2.svg"
        description: "This is an example template for use in the templates extension."
```

The keys are the template identifiers (the file name for the configuration of a single template). Name, previewImage
and description are used for displaying a preview of the chosen template.

A single template configuration looks like this:

```yaml
    page:
        description: this is a superb description of the template
        onCreateEditFields: title,description
        defaults:
            title: some title
            description: and a description
    tt_content:
        1:
            description: This content element should be used for blobbber
            onCreateEditFields: header
            defaults:
                header: a content element header
                CType: div
                colPos: 0
        2:
            description: a hidden element which will only be created
            defaults:
                header: some other content element
                CType: div
                colPos: 0
        3:
            description: and a third element
            onCreateEditFields: header,subheader
            defaults:
                header: enter something sensible here, please
                CType: textmedia
                bodytext: some default bodytext stuff
```

Be aware that you can only configure tables that are stored directly on the page and have a pid field.

## Usage

- Click on the module in the main module menu
- Choose a parent page
- Choose a template
- Fill template variables 
- Click "Save new page"
