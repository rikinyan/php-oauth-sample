# 事前に打つ

create table user (
  user_id int not null primary key auto_increment,
  name varchar(255) not null,
  email VARCHAR(320),
  password varchar(256) not null
);

insert into user(name, email, password) values (
  "rikinyan",
  "aaa@g_gmail.com",
  SHA2('rikinyan_password', 256)
);

insert into user(name, email, password) values (
  "rikinyan2",
  "aaa22@g_gmail.com",
  SHA2('rikinyan_password2', 256)
);

create table client (
  client_id int not null primary key auto_increment,
  name varchar(255) not null,
  confidential bit(1) not null,
  redirect_url varchar(255) not null,
  secret text not null 
);

insert into client (name, confidential, redirect_url, secret) values(
  'app_b',
  0,
  'http://localhost:3000/auth_redirect/',
  SHA2('app_b_pass+http://localhost:3000/auth_redirect/', 256)
);

create table auth_code (
  auth_code varchar(256) not null primary key,
  client_id int not null,
  is_activated bit(1) not null,
  expired_at date not null,
  foreign key (client_id) references client(client_id)
);

create table access_token (
  access_token varchar(255) not null primary key,
  client_id int not null,
  user_id　int not null, 
  expired_at datetime not null,
  foreign key (client_id) references client(client_id),
  foreign key (user_id) references user(user_id) on delete cascade;
);

