$Id: README.txt,v 1.16 2005-02-14 14:01:35 thorstenr Exp $

phpMyFAQ 1.5.0 RC1
Codename "Phoebe"

Installation/Update:
Please read the documentation (documentation.en.html) carefully.

Found a bug?
Please report the bug at our site http://bugs.phpmyfaq.de! Thanks a lot!

Changes since 1.5.0 beta3:
- fixed bug in update script
- fixed some bugs in RSS feeds

Known issues in phpMyFAQ 1.5.0:
- some language files need an update
- using UTF-8 will not work in the PDF files

CHANGELOG:

Version 1.5.0 RC1 - 2005-02-
- full support for PostgreSQL databases
- full support for Sybase databases
- experimental support for MSSQL databases
- LDAP support as an additional option
- one entry in various categories
- mod_rewrite support
- faster template engine parses PHP code
- rewritten PDF export
- complete XML, XHTML and DocBook XML exports
- many code improvements and code cleanup
- better RSS support
- updated bundled htmlArea 3.0 RC3
- PHP5 compatible

Version 1.4.5 - 2005-01-21
- updated Japanese language file
- updated Chinese (Traditional) translation
- some minor bugfixes

Version 1.4.4 - 2004-12-06
- added Romanian translation
- added Chinese (Traditional) translation
- many bugfixes

Version 1.4.3 - 2004-11-05
- added Turkish translation
- added Indonesian translation
- updated German language file
- many bugfixes

Version 1.4.2 - 2004-10-10
- added Finnish translation
- added Hebrew translation
- fulltext search inside admin section
- some accessibility improvements
- many bugfixes

Version 1.4.1 - 2004-08-16
- improved category administration
- added Swedish translation
- added Korean translation
- added Japanese translation
- custom admin menu according to user permissions
- easier record deleting
- session and language stored in a cookie
- less SQL queries in admin section
- updated install script
- updated Chinese language file
- updated Portoguese language file
- improved IDN domain support
- improved accessibility
- many bugfixes

Version 1.4.0a - 2004-07-27
- fixed security vulnerability

Version 1.4.0 - 2004-07-22
- added WYSIWYG Editor
- added Image Manager
- added new category module
- added support for XML-RPC
- added support for timezones
- added ISO 639 support in language files
- added script that converts BBCode in XHTML
- better PDF export with table of contents
- new and better installer
- new XHTML based templates
- automatic language detection
- improved IDN domain support
- password reset function
- include internal links with dropdown box
- many code improvements and code cleanup
- many bug fixes

Version 1.3.14 - 2004-06-09:
- added Slovenian translation
- added Serbian translation
- added Danish translation
- improved performance on Windows Server 2003 and PHP with ISAPI
- fixed some bugs

Version 1.3.13 - 2004-05-17:
- fixed serious security vulnerability (Stefan Esser)
- fixed some bugs

Version 1.3.12 - 2004-04-21:
- added Hungarian language file
- fixed some bugs

Version 1.3.11a - 2004-04-13:
- fixed some annoying bugs

Version 1.3.11 - 2004-04-07:
- added Chinese translation
- added Czech translation
- added support for IDN domains
- many bugfixes

Version 1.3.10 - 2004-02-12
- updated bundled FPDF class
- added Arabic language file
- many bugfixes

Version 1.3.9pl1 - 2004-01-02
- added Vietnamese language file
- bugfixes

Version 1.3.9 - 2003-11-26
- improvements at highlighting searched words
- updated english language file
- BBCode support for the news
- better category browsing
- graphical analysis of votings
- date informations ISO 8601 compliant
- some optimized code
- added Russian language file
- enhanced BB-Code Editor
- fixed some multibyte issues
- improved display of images in PDF export
- some fixes for PHP5
- some bug fixes

Version 1.3.8 - 23.10.2003
- added latvian language file
- fixed italian language file
- bugfix in backup module (IE problem)
- support for MySQL 4.0 full text search
- some improvements in installer (Bug #0000017)
- many bugfixes in the backup module
- better performance in admin area
- fixed cookie problem
- fixed a bug in the BB-Code Parser
- fixed a bug in the language files
- many, many minor bug fixes

Version 1.3.7 - 19.09.2003
- dedicated to Johnny Cash
- added patch against Verisign
- fixed Windows bug in Send2Friend
- some improvements in the BB-Code-Parser
- fixed some layout problems
- many, many minor bug fixes

Version 1.3.6 - 01.09.2003
- fixed bug in installer

Version 1.3.5 - 31.08.2003
- basic internal linking of FAQ records
- RSS-Feed-Export via Cronjob
- solved some PDF problems
- updated english language file
- some improvements in the BB-Code-Parser
- some bug fixes

Version 1.3.4 - 03.08.2003
- improvements at highlighting searched words
- fixed bug in installer (Bug-ID 0000004)
- added new BB-Code-Parser
- added italian translation
- updated dutch and french language file
- little changes in the language files
- many, many minor bug fixes

Version 1.3.3 - 26.06.2003
- better installation
- default password removed
- added IP ban lists
- added portugues translation
- added spanish translation
- selection of default language and setting the password at installation
- changed default language from german to english
- better PDF export with support for pictures
- export all records to one PDF file
- user management improved
- added allow/disallow comment function
- added copyright notice and url in the printer friendly page and the pdf file
- backup files are called phpmyfaq.<date>.sql instead of attachment.php
- fixed bug at installation when using MySQL 4
- fixed bug when deleting news
- fixed bug when deleting comments
- many minor bug fixes

Version 1.3.2 - 25.05.2003
- more verifications in update script
- added new category sorting
- added polish language file
- fixed bug in backup
- added reload locking in voting module
- BB-Code help with multi-language support
- better navigation
- minor bug fixes

Version 1.3.1 - 02.05.2003
- added preview at record editing
- added RSS-Feeds from Top 10, News and latest records
- better navigation in admin area
- system informationen in admin area
- added french language file
- fixed bug in session search
- fixed bugs in adding records
- solved cookie problems
- fixed problem with send2friend link
- fixed delimiter bug with Apache2 and PHP 4.3
- minor bug fixes

Version 1.3.0 - 17.04.2003
- support for multi language records
- enhanced security
- crypted passwords
- admin area uses modules
- PDF support
- more support of XML with XML namespaces and XML schema
- BBCode editor, support for more bb code
- PHP syntax highlighting
- database abstraction layer
- english documentation
- many bugfixes

Version 1.2.5b - 24.03.2003  	
- bugfixes

Version 1.2.5a - 04.03.2003 	
- UBB code bugfixes
- top ten bugfix

Version 1.2.5 - 02.02.2003
- bugfixes

Version 1.2.4 - 31.01.2003
- better checking of variables
- bugs in admin area fixed
- better printing function

Version 1.2.3 - 30.11.2002
- check wether installation oder update script isn't deleted
- fixed bugs in language files, the news module and open questions
- automatic langauge detection in admin area

Version 1.2.2 - 04.11.2002
- minor bug fixes

Version 1.2.1 - 24.10.2002
- better update function and language selection
- solved cookie problems
- many bug fixes, thanks to sascha AT rootforum DOT de

Version 1.2.0 - 09.10.2002
- Template system for free layouts
- fully compatible with PHP 4.1, PHP 4.2 and PHP 4.3 (register_globals = off)
- all color and font definitions with CSS
- better SQL queries
- better category navigation
- better search engine
- better Send2Friend function
- better installation script
- many bugfixes

Version 1.1.5 - 23.06.2002
- minor bug fixes
- russian language file

Version 1.1.4a - 08.06.2002
- minor bug fixes for PHP 4.1.0

Version 1.1.4 - 24.05.2002
- minor bug fixes
- rewrite of PHP code for better performance
- change of the CSS file from style.php to style.css
- better HTML code
- voting can be switched off

Version 1.1.3 - 01.05.2002
- fixed bug in UBB parser
- fixed bugs in viewing comments
- rewrite of the PHP code

Version 1.1.2 - 22.03.2002
- added Send2Friend funktion
- minor bug fixes

Version 1.1.1 - 06.03.2002
- minor bug fixes

Version 1.1.0 - 11.02.2002
- many bugfixes
- more functions in the attachments module
- support for sub categories
- user tracking and admin logging can be switched off
- better installation script
- porting to PHP4
- better PHP code
- admin area supports Netscape 4.x
- no actions could be performed without any records
- better admin logging
- admin account cannot be deleted (security fix)
- better documention

Version 1.0.1a - 15.10.2001
- file ending .php instead of .php3 
 
Version 1.0.1 - 10.10.2001
- fixed bugs in installation and update script

Version 1.0 - 30.09.2001
- minor bug fixes

Version 0.95 - 11.09.2001
- cleaned MySQL table names
- Documentation 
- phpMyFAQ is HTML 4.0 valid
- minor bug fixes
 
Version 0.90 - 23.08.2001
- added update function for version 0.80 
- added question - answer - system
- configurable design of the admin area 
- minor bug fixes
 
Version 0.87 - 20.07.2001
- Top Ten and newest records can be switched off 
- minor bug fixes
 
Version 0.86 - 10.07.2001
- UBB parser fixed
- minor bug fixes
 
Version 0.85 - 08.07.2001
- added backup module (Import and Export) 
- UBB-Code support 
- records can be exported to XML
- minor bug fixes
 
Version 0.80a - 07.06.2001
- minor bug fixes
 
Version 0.80 - 30.05.2001
- added form for questions
- added Top 5 of the newest articles
- added support fo attachments in reocrds in admin area 
- added support for adding records in admin area
- configuration editable in admin area
- showing number of users online
- print function
- better support in writing comments
- bugfix: fixed bad output in comments with HTML
 
Version 0.70 - 27.04.2001
- installation script 
- better right management in admin area
- free designs in configuration possible
- added support for language files (german, english)
- bugfix: fixed problem when deleting comments
 
Version 0.666 - 10.04.2001
- added support for categories
- added voting system
- added support for deleting comments
- minor bug fixes
 
Version 0.65 - 18.03.2001
- added support for comments
- added support for FAQ news
- better search engine
 
Version 0.60 - 22.02.2001
- first released version 

Versions below 0.60 were developer version

The contents of this file are subject to the Mozilla Public License
Version 1.1 (the "License"); you may not use this file except in
compliance with the License. You may obtain a copy of the License at
http://www.mozilla.org/MPL/
 
Software distributed under the License is distributed on an "AS IS"
basis, WITHOUT WARRANTY OF ANY KIND, either express or implied. See the
License for the specific language governing rights and limitations
under the License.

(c) 2001-2004 Thorsten Rinne