# Steps to setup your IDE with code templates and CodeSniffer inspections

1) Go to File -> Import Settings and choose sbDocumentation/PhpStorm/settings.jar

2) Go to PhpStorm -> Preferences -> Editor -> Inspections -> Manage -> Import
   and choose sbDocumentation/PhpStorm/Project_Default.xml
   
3) Got to PhpStrom -> Preferences -> Editor -> File and Code Templates -> Includes -> PHP File Header
   and change the author's name and email adress in the first line
   
4) Install CodeSniffer (might require sudo)
   pear install PHP_CodeSniffer