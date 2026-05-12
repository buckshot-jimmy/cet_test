@echo off
cd  "C:\Apache24\bin"
httpd -k %*
cd "C:\Apache24\htdocs\CET"