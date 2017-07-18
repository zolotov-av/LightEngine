
/*****************************************************************************

  Пример структуры базы данных модуля mod_docs (код для MySQL)

  Пример запуска:

  > mysql -u root -p
  mysql> \. demo_docs.sql

 *****************************************************************************/

CREATE TABLE IF NOT EXISTS docs
(
	doc_id INT PRIMARY KEY auto_increment,
	doc_parent_id INT NULL DEFAULT NULL,
	doc_class_id INT NOT NULL,
	doc_name VARCHAR(80) NOT NULL,
	doc_title MEDIUMTEXT NOT NULL,
	doc_content MEDIUMTEXT NOT NULL DEFAULT '',
	
	UNIQUE INDEX open_doc (doc_parent_id, doc_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS classes
(
	class_id INT PRIMARY KEY auto_increment,
	class_name VARCHAR(32) NOT NULL COLLATE utf8_general_ci,
	class_type ENUM('document', 'plugin', 'system') NOT NULL DEFAULT 'document',
	class_master_id INT NULL DEFAULT NULL,
	
	UNIQUE class_name (class_name),
	INDEX class_master_id (class_master_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS doc_params
(
	param_doc_id INT NOT NULL,
	param_name VARCHAR(80) NOT NULL,
	param_value MEDIUMTEXT,
	PRIMARY KEY (param_doc_id, param_name)
) ENGINE=InnoDB;

INSERT INTO classes (class_id, class_name, class_type, class_master_id) VALUES
	(1, 'SimplePage', 'document', NULL);

INSERT INTO docs (doc_id, doc_parent_id, doc_class_id, doc_name, doc_title, doc_content) VALUES
	(1, NULL, 1, '', 'Wellcome', 'Hello world'),
	(2, 1, 1, 'hello', 'Hello', 'hello world');
