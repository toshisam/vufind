###  Setting up the National Licence
- Update the db by executing the script located in `/home/nicolas/PhpstormProjects/swissbib/source/sbDocumentation/vf3sb_mysql_migrate_swiss_national_licences.sql`
- Complete the missing information in the configuration according with the production options
(file `module/Swissbib/config/module.config.php on section` on  section `swisbib.national_licence`)
- Set the apache environment variable on the `/etc/apache2/sites-available/test.swissbib.ch.conf` :
    ```bash
    #Swiss National Licence specific configuration
    SetEnv SWITCH_API_PASSW xxxxx
    SetEnv SWITCH_API_USER xxxxx
    ```
    Run `sudo service apache2 restart` for make these variable accessible from the application
- Run `cli/cssBuilder.sh` for compile the css style


### Console commands (called by cron job)
It is possible to run the following commands via a cron job.
##### Before run the script
- Run `cli/createClassMapFiles.sh` to create the classmap files used by the console command
- Remove the `cli` caches by running  `rm -rf local/cache/cli/`. Be careful to be in the root 
path before run this command.


##### Export of the National Licence users
Please make sure you have correctly setup the SMTP server config.
(`module/Swissbib/config/module.config.php on section` on `swisbib.national_licence.smtp_options`) to support the TLS encryption.
```bash
sudo cli/send-national-licence-user-export.sh 
```

##### Maintenence task
```bash
sudo cli/update-national-licence-user-info.sh
``` 


