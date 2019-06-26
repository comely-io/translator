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
* Language name MUST be in all lowercase;
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

### Translations

* Language files MUST only contain translations for words and sentences in a SINGLE line;
* For full page or paragraph translations, it is better to use `if` and `else` clauses directly in page source.
* All translations keys SHOULD BE in lowercase;
* Translation keys may only contain alphabets, numbers and an underscore (`a-z0-9\_`).

*sample `en-us/dictionary.yml`*
```yaml
email: E-mail
email-addr: E-mail address
username: Username
password: Password

profile:
  name:
    complete: Full name
    first: First name
    last: Last name
  address:
    line1: Address line 1
    line2: ""
    country: Country
    city: City
    state: State/Province
    zip: Postal/ZIP code
```

Based on above example, retrieving translation for `profile.name.complete` will return "Full Name" when `en-us` is selected as language.

### Fallback language

* It is RECOMMENDED to set a `fallback` language;
* If a translation is missing in files of currently loaded language, translation method will then retrieve translation from fallback language.

### Loading of Translation Files

* `load` method must be called prior to any call to translation functions.
* Select only relevant translation files to load, i.e. if a controller does not have any form validation or other action then it may be unnecessary to require `messages` file.

```php
$translator->load()
  ->dictionary()
  ->messages()
  ->sitemap()
  ->misc();
```

### Caching

* Caching of compiled language files is highly recommended.
* Provide `cachingDirectory` method with an instance of `Comely\Filesystem\Directory` to a writable cache directory to enable caching;

## Global Translation Functions

Function | Parameters | Returns | Description
--- | --- | --- | ---
__() | `string` $key, `null`/`string` $lang | `null`/`string` |Retrieves a translation
__k() | `string` $key, `null`/`string` $lang | `string` | Retrieves a translation, if none found, returns $key
__f() | `string` $key, `array` $args, `null`/`string` $lang | `null`/`string` | Retrieves a translation and runs `vsprintf()`