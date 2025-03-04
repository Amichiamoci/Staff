#!/bin/bash

TARGET=./starting-db.tmp.sql

function copy_base() {
    echo -e "--\n-- db-schema.sql\n--\n" >> $TARGET
    cat queries/db-schema.sql >> $TARGET
}
function build_functions() {
    echo -e "\nSTART TRANSACTION;\n" >> $TARGET
    for i in $(ls queries/functions/ | grep ".sql"); do 
        echo -e "--\n-- functions/$i\n--\n" >> $TARGET
        cat "queries/functions/$i" >> $TARGET
        echo -e "\n" >> $TARGET
    done
    echo -e "\nCOMMIT;\n" >> $TARGET
}
function build_procedures() {
    echo -e "\nSTART TRANSACTION;\n" >> $TARGET
    for i in $(ls queries/procedures/ | grep ".sql"); do 
        echo -e "--\n-- procedures/$i\n--\n" >> $TARGET
        cat "queries/procedures/$i" >> $TARGET
        echo -e "\n" >> $TARGET
    done
    echo -e "\nCOMMIT;\n" >> $TARGET
}
function build_views() {
    echo -e "\nSTART TRANSACTION;\n" >> $TARGET
    echo -e "--\n-- views/*\n--\n" >> $TARGET
    cat queries/views/*.sql >> $TARGET
    echo -e "\nCOMMIT;\n" >> $TARGET
}

copy_base
build_functions
build_procedures
build_views