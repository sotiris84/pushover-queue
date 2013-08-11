CREATE TABLE `notifications` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(256) NOT NULL DEFAULT '',
  `message` varchar(256) NOT NULL DEFAULT '',
  `priority` int(1) NOT NULL DEFAULT '-1',
  `timestamp` int(11) NOT NULL,
  `sent` int(1) NOT NULL DEFAULT '0',
  `sentTimestamp` int(11) DEFAULT NULL,
  `sentStatus` int(11) DEFAULT NULL,
  `sentHTTPStatus` varchar(3) DEFAULT NULL,
  `sentRequestID` varchar(32) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;