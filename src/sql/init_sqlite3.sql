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
    title varchar(200) not null,
    creation_date time not null default current_timestamp,
    foreign key (id_owner) references users(id_user)
);

create table participants (
    id_project integer  not null,
    id_participant  integer not null,
    creation_date time not null default current_timestamp,
    primary key (id_project,id_participant),
    foreign key (id_participant) references users(id_user),
    foreign key (id_project) references projects(id_project)  
);

create table abonnements (
    id_user integer not null, 
    id_abonnee integer not null, 
    primary key(id_user, id_abonnee),
    foreign key (id_user) references users(id_user),
    foreign key (id_abonnee) references users(id_user)
);

create table brouillons (
    id_user integer not null,
    id_project integer not null,
    primary key(id_user, id_project),
     foreign key (id_user) references users(id_user),
    foreign key (id_project) references projects(id_project)

);

-- 
-- DONNEES EXEMPLE
-- 

-- admin account (admin_php_starter@yopmail.com:admin)
insert into users(email, password_hash, user_role, username) values ('admin_tifod@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'admin', 'xX_The_Big_Boss_Xx');

insert into users(email, password_hash, user_role, username) values ('test@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'normal_user', 'Test');
insert into users(email, password_hash, user_role, username) values ('test1@yopmail.com', '$2y$12$hA2wxJZhBLdHPJPQHQA.2e.sSUOqI/HAndSH8/9LD9WHn.cZ8qfz2', 'normal_user', 'Test1');

insert into projects(id_owner, title) values (1, 'Notre premier projet');
insert into projects(id_owner, title) values (2, 'Notre deuxieme projet');

insert into participants(id_project,id_participant) values(2,1);

insert into abonnements (id_user, id_abonnee) values(2,1);
insert into abonnements (id_user, id_abonnee) values(2,3);
insert into abonnements (id_user, id_abonnee) values(3,2);
insert into abonnements (id_user, id_abonnee) values(3,1);

insert into brouillons (id_user, id_project) values(1,1);
