
/*****************************************************************************

  Пример структуры базы данных для MySQL

  Файл для создания базы данных MySQL для демонстрационных примеров.
  Пользователь должен сам определить структуру базы данных, содержимое данного
  файла можно взять за основу при создании таблиц для модулей включенных в
  движок.

  Пример запуска:

  > mysql -u root -p
  mysql> \. demo_db.sql

  Примечание: прежде чем запускать скрипт, проанализируйте код и внесите свои
    правки. Наиболее опасные операции я по умолчанию закоментировал.

 *****************************************************************************/

/* создание базы данных */
-- CREATE DATABASE light_demo DEFAULT CHARACTER SET utf8;

/* создать пользователя, и дать ему права на базу данных */
-- GRANT ALL ON light_demo.* TO engine@localhost IDENTIFIED BY 'your_password';

/* переключаемся на нашу базу данных */
-- USE light_demo;

CREATE TABLE IF NOT EXISTS config
(
  config_name VARCHAR(80) NOT NULL,
  config_value MEDIUMTEXT,
  
  PRIMARY KEY (config_name)
) ENGINE=InnoDB;

-- INSERT INTO config (config_name, config_value) VALUES ('site_prefix', '/');


CREATE TABLE IF NOT EXISTS sessions
(
  session_id CHAR(32) NOT NULL,
  session_form_sid CHAR(32) DEFAULT NULL,
  session_user_id INT(11) DEFAULT NULL,
  session_start INT(11) DEFAULT NULL,
  session_time INT(11) DEFAULT NULL,
  session_user_ips MEDIUMTEXT,
  session_autologin TINYINT(1) DEFAULT NULL,
  session_domain CHAR(40) NOT NULL DEFAULT 'default',
  
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB;


CREATE TABLE users
(
  user_id INT(11) unsigned NOT NULL AUTO_INCREMENT,
  user_name VARCHAR(240) DEFAULT NULL,
  user_login VARCHAR(30) DEFAULT NULL,
  user_passwd CHAR(32) DEFAULT NULL,
  user_status ENUM('active','removed') NOT NULL DEFAULT 'active',
  user_mail VARCHAR(100) DEFAULT NULL,
  user_phone VARCHAR(80) NOT NULL DEFAULT '',
  user_regtime INT(11) DEFAULT NULL,
  
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `user_login` (`user_login`),
  KEY `user_mail_idx` (`user_mail`)
) ENGINE=InnoDB;

/* отобразить таблицы */
SHOW TABLES;
