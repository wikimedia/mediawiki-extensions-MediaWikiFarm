*************
Configuration
*************

MediaWikiFarm can be configured to manage multiple farms and, for each farm, the lists of existing wikis, the configuration files, and the versions can be configured.

Environment
===========

All the config files are relative to the configuration directory. The main config file is :path:`farms.yml` (or :path:`farms.json` or :path:`farms.php`). All other config files are declared in this main config file, and it is entirely up to you to decide how to organise the various files.

Config files can be written in YAML_, JSON_, or PHP syntax, and all are cached in serialised format (if configured during the installation). YAML requires an external library (Symfony YAML) and this comes with a performance cost (3ms) but, if cache is used, this cost is only paid when config files are changed.

A `JSON Schema`_ is available for validation of the main config file (YAML and PHP are transformed to JSON before validation). An external library is however needed for this validation, it can be installed with the 'require-dev' section of Composer.

Main config file
================

The main config file is a dictionary. Here is an example in YAML syntax with all existing features:

::

    # A farm similar to the Wikimedia one (except for organisation of config files)
    wikimedia:
        
        server: '(?<lang>[a-z-]+)\.(?<family>[a-z]+)\.org'
        
        variables:
            
            - variable: 'family'
            
            - variable: 'lang'
              file: '$family.dblist'
        
        suffix: '$family'
        wikiID: '$lang$family'
        versions: 'wikiversions.json'
        data: '/srv/data/$family/$lang'
        config:
          - file: 'DefaultSettings.yml'
            key: 'default'
          - file: 'Settings-$family.yml'
            key: '*$family'
            default: '$family'
          - file: 'PrivateSettings.yml'
            key: '*'
          - file: 'ExecSettings.php'
            executable: true
    
    # Internally redirect .com to .org (obviously it can also be done on the HTTP level)
    '(?<lang>[a-z]+)\.(?<family>[a-z]+)\.com':
        
        redirect: '$lang.$family.org'

Each key is the (arbitrary) name of a farm, and values are the specific farm configuration.

In a (non-redirect) farm, three subkeys are required, 'server', 'family', and 'wikiID', and the subkey 'config' should exist (else MediaWiki default parameters will be used). In a redirect, there must be two subkeys: 'server' and 'redirect'.

Server
------

The most important subkey is 'server': it is a regular expression of the server name/domain name and possibly of the subdirectory (or even multiple subdirectories). There should be only one regex, which matchs a given server name. It is recommanded to use named patterns in the regex to capture parts of the server name to construct the wikiID and suffix.

Redirect
--------

To avoid configuration duplicates, it is possible to internally redirect to another farm. Obviously you should enlarge existing regexes when possible (e.g. match two TLD), but you can use these redirects to avoid overcomplicated regexes, for instance to manage exceptions to a general schema. When the 'redirect' subkey is used, there must be no other subkeys.

Variables
---------

The second thing to configure is the 'variables' subkey. This must be an ordered list of dictionaries. For each declared variable, it can be checked if the pattern from the regex exists. If it doesn’t exists, the wiki doesn’t exist, and a 404 page is displayed. Else, it continues to the next variable. If there is no file written in this section, no check is done and the variable is assumed to exist. The file names can contain all variables already checked (but not the current variable).

Each of these files must contain either a list of the existing values for the given variable, either a dictionary where keys are existing values for the given variable and values are MediaWiki versions. The second form is only useful for the multiversion mode and for preparation of a transition from monoversion to multiversion mode. These files can be in any previously mentionned format (YAML, JSON, PHP) or, for simple lists, in .dblist format (each line contains only a value; format used by Wikimedia).

Suffix and wikiID
-----------------

Once the variables are correctly defined, the 'suffix' and 'wikiID' subkeys must be configured. As explained in the :doc:`concepts`, they must be carefully chosen, they must be uniquely defined, and the suffix of the 'wikiID' subkey must match the 'suffix' subkey. Although it is possible to change these values, any change will impact configuration files, and it could create a mess if you don’t change it in all your config files. So a strong advice is to choose it carefully at the beggining and do not change after.

Versions
--------

The 'versions' subkey is only relevant in multiversion mode and if versions are not already in the files corresponding to the variables. The file linked here must be a dictionary where keys are wikiIDs, suffixes, or 'default' (in this order of precedence), and the values are the corresponding MediaWiki versions.

[todo: this part has not been tested as of now, it could not work properly.]

Data
----

[todo: this is currently unused.]

The 'data' subkey link to a directory where lays all stuff of the given wiki, mainly images, cache, and assets.

Configuration files
-------------------

The 'config' subkey is a list of dictionaries, each one containing always a 'file' key linking to a specific config file in YAML, JSON, or PHP syntax. The last files have precedence over the first if they redefine a value within a given priority (wikiID, tags, suffix, default). However, the final configuration always takes into account the priority defined: wikiID, tags (in the order of definition), suffix, default (in this order of precedence); this is the maximum precedence rule, even for arrays (see below).

As said above, you can organise your config files as you want, and even define a single file containing all configurations. However a thing to keep in mind is the config files are cached separately, hence each time you change a config file, cache is rebuilt for each wiki it is impacting; if you have only one file, each changes will always rebuild configurations for all wikis. In addition of performance cost, any syntax error will crash all wikis it is impacting. An argument in favour of a single configuration file is there is only one file to be read, hence possibly a performance gain; but this could also impact negatively the performance if the file is too big compared to many smaller files.

Schema of the config files
^^^^^^^^^^^^^^^^^^^^^^^^^^

The schema of each file depends on the other key defined here:

* If there is a subkey 'key' with value 'default', the file content must be a dictionary where keys are MediaWiki configuration parameters and values are the corresponding values. The corresponding priority is 'default'.

* If there is a subkey 'key' with a value '*', the file content must be a dictionary where keys are MediaWiki configuration parameters and values must be dictionaries where keys are wikiIDs or tags or suffixes or 'default' and values are the corresponding values. The corresponding priority depends on the keys.

* If there is a subkey 'key' with another value containing '*' (mandatory character), the file content must be a dictionary where keys are MediaWiki configuration parameters and values must be dictionaries with keys (which will be interpreted by replacing the star by the key and by replacing other variables) and values are the corresponding values. The corresponding priority depends on the resulting keys. There should be also a subkey 'default' containing only variables; any key named 'default' in the file content will be replaced by this value.

* If there is a subkey 'executable' with boolean value 'true', the file content is interpreted as a raw PHP and will always be executed after all dictionaries listed above are executed. Hence it have a super-priority, in addition of the fact all the power of PHP can be used.

In order to make to the configuration easier to read, it is adviced to only use PHP files where it is required: definition of functions, conditionnally define configuration parameters (if their unconditional presence is harmful).

Specific case of the arrays
^^^^^^^^^^^^^^^^^^^^^^^^^^^

During the compilation of the configuration (by the class SiteConfiguration of MediaWiki), the highest-priority value is kept for scalar values (booleans, strings, numbers). For arrays, they are recursively merged together by order of priority, but possibly some priorities can apply together. Here are the subtleties for the merge rules:
* for numeric arrays (lists), high-priority values values prepends lesser-priority values;
* associative arrays are recursively merged with the high-priority key having precedence over less-priority values, except in the case the lesser-priority value is scalar and evaluated to true, in which case case has precedence (a consequence is the permissions array can only have 'true' values if there is only this rule);
* when the wikiID/tag/suffix has a prefix '+', it lets underneath priorities apply as well; this is probably wanted for the permissions array to add all permissions of all priorities.

These rules, although complicated as expressed in their formal definitions, are quite natural: scalar highest-values are kept, arrays are merged with highest-priority keys having precedence.

If it is wanted to force values to false in an array, an additional rule has been added in MediaWikiFarm for the array 'wgGroupPermissions' (only): the pseudo config parameter '+wgGroupPermissions' can set values evaluated to false in order to remove permissions to previously-added permissions.

See the example below in YAML syntax:

::

    +wgGroupPermissions:
      default:
        '*':
          read: false
          edit: false
        user:
          read: true
      +mywiki:
        '*':
          read: true
        user:
          apihighlimits: true
          edit: false
        autoconfirmed:
          edit: true

In this example, if there is no other section, the 'wgGroupParameter' will have its MediaWiki value but:
* by default (i.e. on all wikis, when no higher priority rule override it):

  * reading, editing, and account creation are disabled for the MediaWiki group containing everyone (logged-in users and anonymous users);

  * reading is enabled for the MediaWiki group 'user' (containing all logged-in users), and editing is enabled for users because MediaWiki explicitely gives this permission to users in its default configuration;

* on the wiki 'mywiki' (if we assume it is a unique wiki and not a tag or a suffix):

  * reading is enabled for the MediaWiki group containing everyone (MediaWiki default configuration permits reading for this group, but since we overrided it above, this value is important to re-enable it), but editing is disabled for this group (definition above apply);

  * the users are granted the 'apihighlimits' (that’s wonderful, no? :), they can read because the default rule above permit it (but NOT because the MediaWiki default value is true, since the pseudo-parameter '+wgGroupPermissions' has precedence over 'wgGroupPermissions') (and secondly because the group 'everyone' can read, but this is MediaWiki affairs to do this merge), and they cannot edit;

  * the MediaWiki group 'autoconfirmed' (users with some oldness, as defined by other MediaWiki parameters) can edit.

.. _YAML: http://www.yaml.org
.. _JSON: http://www.json.org
.. _JSON Schema: http://json-schema.org

