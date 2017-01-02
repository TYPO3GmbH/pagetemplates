Page Templates for TYPO3
========================

Provides yaml based templates for TYPO3 pages. Allows an editor to select a page template for creating a new page.
Allows editing of page and content properties on creation.

Configuration
-------------

### Page TSConfig:

| Page TSConfig | Default | Description |
| --------------|---------|-------------|
| mod.web_PagetemplatesTxPagetemplates.storagePath | | Path to the directory containing the YAML configuration files (EXT: syntax is supported) |


#### YAML:

Folder structure:

- Templates.yaml
  - Structure
    - template1.yaml
    - template2.yaml
    
    
Main Templates.yaml file contains the configuration for the available templates:

```yaml
    example1:
        name: "Example Template 1"
        previewImage: "EXT:pagetemplates/ext_icon.svg"
        description: "This is an example template for use in the templates extension."
    example2:
        name: "Example Template 2"
        previewImage: "EXT:pagetemplates/ext_icon.svg"
        description: "This is an example template for use in the templates extension."
```

The keys are the template identifier (the file name for the configuration of a single template). Name, previewImage
and description are used for displaying a preview of the chosen template.

A single template configuration could for example look like this:

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
