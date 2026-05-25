..  include:: /Includes.rst.txt

PPL DeepL V2 Translate
#######################

PPL DeepL V2 Translate provides DeepL based text and file translation for TYPO3 12.4 LTS.

Features
--------

* Frontend content element for text translation.
* Frontend content element for file translation.
* Backend module for text translation.
* Backend module for file translation.
* Backend configuration for DeepL languages, approved glossaries and frontend access.
* Optional DeepL glossary usage for consistent terminology.
* Optional writing style and tone settings where DeepL supports them.

Frontend
--------

Editors can add the TYPO3 content elements ``PPL DeepL V2 Translation`` and ``PPL DeepL V2 File Translation`` to a page. The text element translates entered text. The file element uploads a supported document, sends it to DeepL and returns the translated file.

Backend
-------

The extension adds a ``PPL DeepL`` backend module group with translation, file translation and configuration modules. The configuration module is used to load DeepL languages, approve glossaries and configure frontend access.

Requirements
------------

* TYPO3 CMS 12.4 LTS
* PHP 8.2 or newer
* DeepL API key

License
-------

This extension is released under the GNU General Public License version 2.0 or later, the standard TYPO3 extension license.
