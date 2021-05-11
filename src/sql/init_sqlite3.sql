create table _enum_user_role (
    description varchar(50) not null primary key
);
insert into _enum_user_role(description) values ('admin');
insert into _enum_user_role(description) values ('normal_user');

create table users (
    id_user integer primary key not null,
    user_role varchar(50) not null,
    username varchar(50) not null,
    email varchar(200) default null unique,
    password_hash text default null,
    last_user_update time not null default current_timestamp,
    foreign key (user_role) references _enum_user_role(description)
);

create table projects (
    id_project integer primary key not null,
    id_owner integer not null,
    title_project varchar(200) not null,
    image_project varchar(200),
    creation_date time not null default current_timestamp,
    foreign key (id_owner) references users(id_user)
);

-- 
-- DONNEES EXEMPLE
-- 

-- admin account (admin_php_starter@yopmail.com:admin)
insert into users(email, password_hash, user_role, username) values ('admin_tifod@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'admin', 'xX_The_Big_Boss_Xx');

insert into projects(id_owner, title) values (1, 'Notre premier projet');