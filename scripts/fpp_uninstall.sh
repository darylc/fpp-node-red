#!/bin/bash
sudo systemctl stop mosquitto.service
sudo systemctl disable mosquitto.service
sudo systemctl stop node-red.service
sudo systemctl disable node-red.service
sudo rm /etc/systemd/system/node-red.service
sudo systemctl daemon-reload
sudo apt-get -y remove npm

