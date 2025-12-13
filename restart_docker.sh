#!/bin/bash
echo "Stopping old containers..."
sudo docker-compose down

echo "Rebuilding and starting new containers..."
sudo docker-compose up --build -d

echo "Done! Check status with: sudo docker ps"
