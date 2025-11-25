# What is this?

This folder contains a snapshot of the data pulled from the troop database on 11/1/2024.  Use this to bootstrap a test version of the website

#how do I use it?

Once you have created the database with user and password, use the following command to create all of the tables and add the snapshot data in: 

> sudo mariadb -D u321706752_t212 -u u321706752_t212db -p < u321706752_t212.sql


Then, create the procedures in the DB that the website needs:

> sudo mariadb -D u321706752_t212 -u u321706752_t212db -p < create_procedures.sql
