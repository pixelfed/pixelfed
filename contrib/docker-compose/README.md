# Pixelfed + Docker + Docker Compose

## Prerequisites

* One of the `docker-compose.yml` files in this directory
* A copy of the `example.env` file

In order to set configuration, please use a .env file in your compose project directory (the same directory as your docker-compose.yml), and set database options, application
name, key, and other settings there.

A list of available settings is available in .env.example

The services should scale properly across a swarm cluster if the volumes are properly shared between cluster members.
