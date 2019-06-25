# Translator Specification

Compiled cached translation file for a single language may scale up to hundreds of KBs or even in MBs, which is a serious and
unnecessary overhead on HTTP requests served by your web server; Translator component solves this problem by dividing 
translations in groups and then loading/storing them separately or in groups.

### Translator Class
* `Comely\Translator\Translator` is a singleton class; This is required for global translation functions to work.
* Constructor requires instance of `Comely\Filesystem\Directory` to which contains all translations.

### Languages
* A language is a sub-directory with in translations directory;
* Language name MUST either be 2 alphabets (`en`,`lt`) or 4 alphabets with a hyphen in middle (`en-us`)
* Language name MUST be in all lowercase; This may be against standard, but has maximum com
* Language directory must contain following files:

```
translations/
└─── en-us/
│    │    dictionary.yml
│    │    messages.yml
│    │    sitemap.yml
│    │    misc.yml
└─── ur-pk/
```

#### YAML files

| Name | Description
| --- | ---
dictionary.yml | Should contain vocabulary; Translations for single words, etc...
messages.yml | Should contain error/success messages for all controllers, models, etc...
sitemap.yml | Should contain translations for all navigation menus and page titles, etc..
misc.yml | Should contain tool tips and any other translations that are not covered in above files.