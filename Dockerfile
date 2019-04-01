#Install server
FROM ubuntu:14.04.4
#Download base image php
FROM php:5.6-cli
RUN apt-get update && apt-get install -y apt-transport-https
RUN echo 'deb http://private-repo-1.hortonworks.com/HDP/ubuntu14/2.x/updates/2.4.2.0 HDP main' >> /etc/apt/sources.list.d/HDP.list
RUN echo 'deb http://private-repo-1.hortonworks.com/HDP-UTILS-1.1.0.20/repos/ubuntu14 HDP-UTILS main'  >> /etc/apt/sources.list.d/HDP.list
RUN echo 'deb [arch=amd64] https://apt-mo.trafficmanager.net/repos/azurecore/ trusty main' >> /etc/apt/sources.list.d/azure-public-trusty.list
# Update Ubuntu Software repository
#RUN apt-get update\
#install mysql server
#RUN apt install mysql-server
#Add usergroup for MySql
#RUN groupadd -r mysql && useradd -r -g mysql mysql

