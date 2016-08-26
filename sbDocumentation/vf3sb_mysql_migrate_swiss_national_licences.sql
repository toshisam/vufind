--
-- Table structure for table `swiss_national_licence`
--

/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `swiss_national_licences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `edu-id` varchar(255) DEFAULT NULL,
  `condition_accepted`  BOOLEAN DEFAULT FALSE,
  `request_swiss_mobile_phone` BOOLEAN NOT NULL DEFAULT FALSE,
  `date_expiration` datetime DEFAULT NULL,
  `blocked` BOOLEAN DEFAULT FALSE,
  `postal_address` varchar(255) DEFAULT NULL,
  `last_edu_id_activity` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE `edu-id` (`edu-id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;