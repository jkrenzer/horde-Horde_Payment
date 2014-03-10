-- $Horde: incubator/Horde_Payment/scripts/sql/horde_payment.sql,v 1.2 2009/02/05 18:07:39 duck Exp $

CREATE TABLE payment_authorizations (
  authorization_id CHAR(13) NOT NULL,
  module_name VARCHAR(32) NOT NULL,
  module_id VARCHAR(32) NOT NULL,
  amount FLOAT NOT NULL,
  user_uid VARCHAR(32) NOT NULL,
  created INT NOT NULL,
  updated INT NOT NULL,
  status SMALLINT NOT NULL,
  method VARCHAR(32) NOT NULL,
--
  PRIMARY KEY  (authorization_id),
);

CREATE UNIQUE INDEX module_index ON payment_authorizations (module_name, module_id);

CREATE TABLE payment_authorizations_attributes (
  authorization_id CHAR(13) NOT NULL,
  attribute_key VARCHAR(85) NOT NULL default '',
  attribute_value TEXT,
  created int(11) NOT NULL,
  updated int(11) NOT NULL,
--
  PRIMARY KEY  (authorization_id, attribute_key)
);

CREATE TABLE payment_methods (
  module VARCHAR(32) NOT NULL,
  method VARCHAR(32) NOT NULL,
--
  PRIMARY KEY  (module,method)
);
