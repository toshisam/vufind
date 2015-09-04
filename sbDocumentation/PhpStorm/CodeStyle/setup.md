# Steps to setup your IDE with code templates and CodeSniffer inspections

1) Go to File -> Import Settings and choose sbDocumentation/PhpStorm/CodeStyle/settings.jar

2) Go to File -> Settings -> Editor -> Inspections -> Manage -> Import
   and choose sbDocumentation/PhpStorm/CodeStyle/Swissbib_Default.xml
   
3) Got to File -> Settings -> Editor -> File and Code Templates -> Includes -> PHP File Header
   and change the author's name and email adress in the first line
   
4) Install Code Sniffer (might require sudo)
   pear install PHP_CodeSniffer

5) Configure Code Sniffer in PhpStorm. Go to File -> Languages & Frameworks -> PHP -> Code Sniffer
   Configure the path in development environment as in Screenshot CodeSnifferSetup.png