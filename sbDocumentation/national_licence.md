## Setting up the National Licence
- Update the db by executing the script located in `/home/nicolas/PhpstormProjects/swissbib/source/sbDocumentation/vf3sb_mysql_migrate_swiss_national_licences.sql`
- Complete the missing information in the configuration according with the production options
(file `module/Swissbib/config/module.config.php on section` on  section `swisbib.national_licence`)


## Cron job

It is possible to run the following command via a cron job:
Export of the National Licence users. Please make sure you have correctly setup the SMTP server
(`module/Swissbib/config/module.config.php on section` on `swisbib.national_licence.smtp_options`) to support the TLS encryption.
```bash
sudo cli/send-national-licence-user-export.sh 
```

Maintenence task:
```bash
sudo cli/update-national-licence-user-info.sh
``` 