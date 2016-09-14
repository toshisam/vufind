--
-- Table structure for table `swiss_national_licence`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `national_licence_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edu_id` varchar(255) NOT NULL,
  `persistent_id` varchar(255) NOT NULL,
  `user_id` int(11) DEFAULT NULL,

  `home_organization_type` varchar(255) DEFAULT NULL,
  `mobile` VARCHAR(20) DEFAULT NULL,
  `home_postal_address` varchar(255) DEFAULT NULL,
  `affiliation` varchar(255) DEFAULT NULL,
  `swiss_library_person_residence` varchar(10) DEFAULT NULL,

  `condition_accepted`  BOOLEAN DEFAULT FALSE,
  `request_temporary_access` BOOLEAN NOT NULL DEFAULT FALSE,
  `date_expiration` datetime DEFAULT NULL,
  `blocked` BOOLEAN NOT NULL DEFAULT FALSE,
  `last_edu_id_activity` datetime DEFAULT NULL,
  `created` datetime  DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES user(`id`),
  UNIQUE `edu_id` (`edu_id`),
  UNIQUE `persistent_id` (`persistent_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;