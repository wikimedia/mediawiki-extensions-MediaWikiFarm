********
Concepts
********

It is explained here the vocabulary and the big picture of how MediaWiki and MediaWikiFarm manages multiple wikis.

History
=======

MediaWiki has a long history of managing multiple wikis, since Wikimedia quickly had to manage many projects (Wikipedia, Wikisource, etc.) and many wikis by project (many languages for each project). MediaWiki (called “phase3” at the time) was born during 2002-2003; the SiteConfiguration class, which manages multiple configurations, was created mid-2004 (according to Git history). The definitions below are a legacy of this class SiteConfiguration, which is still used internally to compile the configurations.

Definitions
===========

The basic concept is the **farm**, which is a set of wikis, whose the configuration is collectively managed by an operator. For example, Wikimedia wikis are collectively a farm of wikis, Wikia wikis are a farm of wikis, the Wikimedia Beta Cluster is another farm (whose the aim is to create a pre-production environment for the main Wikimedia farm).

The wikis are represented by a **wikiID**, an arbitrary name to identify individual wikis. In the original spirit of the farm concept, the wikiID is the database name of the wiki, but the wikiID can be more generally though as "just identifiers", linked or not to the database name. For instance, Wikimedia wikis have wikiIDs strictly linked to the database name of the wiki: "enwiki" for English-speaking Wikipedia, "huwiki" for Hungarian-speaking Wikipedia, "nvwiki" for Navajo-speaking Wikipedia, "frwiktionary" for French-speaking Wiktionary, etc.

The wikis can be naturally sorted according to their **suffix**, their canonical family. As the name suggests it, the suffix of wikiIDs must match a suffix. The exact meaning can be defined farm by farm; and if there is no such natural classification, an arbitrary suffix can be defined (but a non-empty suffix must be defined). For Wikimedia wikis the natural families are the projects: "wiktionary" for the Wiktionaries, "wikivoyage" for the Wikivoyage sites, "wiki" for Wikipedia (for historical reasons). In a farm where you have multiple distincts groups of people, for instance clients or communities, you can define a suffix as a group of people, and each group can own some wikis.

Other classifications can be defined [todo: but currently not implemented in MediaWikiFarm]: wikis can be grouped by **tags**. Here the list of wikis must be manually defined. For instance Wikimedia has tags such: wikis where the VisualEditor is proposed as a Beta Feature, read-only wikis, private wikis, small wikis, or the database cluster of the wikis (there are currently 7 clusters), or the deployment group (there are currently 3 groups: test wikis, all wikis except Wikipedia, and Wikipedia).

Configuration
=============

MediaWiki has many configuration parameters (currently about 730 parameters). Although each parameter has a default value, each wiki must change at least 5 to 10 parameters, and often between 20 and 50 parameters are changed. In a farm, this can become difficult to manage. Hence, the above classification can help to set parameters to groups of wikis.

For instance, take the parameter $wgDefaultSkin, the skin used for anonymous visitors.
1. by default, MediaWiki defines "vector" as default skin;
2. you can prefer a default to "modern" in your farm;
3. different groups of people, who have each their suffixes, prefer respectively "nimbus" and "cologneblue";
4. for the anniversary event of the farm, each group of people creates a dedicated wiki for the event, and a common graphic identity is decided with the "metrolook" skin;
5. in order to get more flexibility for the portal of the anniversary event, the skin "chameleon" (Bootstrap) is used with a custom stylesheet.

Such a complicated scenario can be easily implemented if the classifications have been correctly defined:
2. the default value for the farm is "vector";
3. the two suffixes get the respective values "nimbus" and "cologneblue";
4. a tag is created for the wikis of the event, and it get the value "metrolook";
5. the portal website get the value "chameleon".
(I have a bad sense of the graphic aesthetic, forget me if the choices above are weird :)

URL, variables, and existing wikis
==================================

A farm is identified by a regular expression of its URL. For instance, for a subset of the Wikimedia farm, it could be "[a-z-]{2,12}\.wik[a-z]+)\.org". With MediaWikiFarm, it can be convenient to name each part of the URL, this will create variables, which can be reused later: "(?<lang>[a-z-]{2,12})\.(?<family>wik[a-z]+)\.org".

These variables can be used to check a given wiki does exist. For instance, the list of the existing "family"s can be defined in a file "families.yml". Similarly the list of the existing "wiki"s for a given family can be defined in a file "$family.yml" (NB: the dollar sign represents a variable).

To check if "zh-min-nan.wikipedia.org" exists:
1. "wikipedia" is searched in the file "families.yml", and if it exists,
2. "zh-min-nan" is searched in the file "wikipedia.yml".

When many farms are defined, the initial choice depends on the regular expressions, so these should be mutually exclusive. This is sort of "virtual hosts" at the MediaWiki-level.

It is possible to redirect from a farm (regular expression) to another (up to 5 times); this redirect is internal and the visitor will not be aware of it.

Versions
========

MediaWikiFarm has two modes:
1. monoversion: only one version of MediaWiki is available for the whole farm, or
2. multiversion: different versions of MediaWiki co-exist in the farm.

[todo: note the mode is currently fixed for all the farms, it should be defined farm-by-farm; it is probaly possible.]

Here, what is called a **version** is merely a MediaWiki version + flavour. For instance, given SemanticMediaWiki is installed with Composer, and it is difficult to deactivate it for a given wiki, it can be convenient to install a MediaWiki with SemanticMediaWiki, and another MediaWiki without it. Strictly, a version here is the name of the directory containing the MediaWiki specific version + flavour. In monoversion mode, there is no version in this definition (since the directory name is not intended for that).

It is possible to easily switch from a monoversion mode to a multiversion mode (the reverse is also true, but probably less useful).

