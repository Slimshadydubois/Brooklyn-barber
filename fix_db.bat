D:\xampp\mysql\bin\mysql.exe -u root -e "DROP DATABASE IF EXISTS barbearia; CREATE DATABASE barbearia;"
D:\xampp\mysql\bin\mysql.exe -u root barbearia < db\baseDATABASE.sql
D:\xampp\mysql\bin\mysql.exe -u root barbearia < db\update_schema.sql
D:\xampp\mysql\bin\mysql.exe -u root barbearia < db\seed.sql
