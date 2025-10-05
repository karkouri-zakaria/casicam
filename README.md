# casicam
---
<p align="center">
  <img src="https://github.com/user-attachments/assets/84cb627d-7686-8143-2625cd7d90fe" width="50%" />
</p>
casicam is a web application designed to showcase contact information of casicam beauty center office in an elegant and responsive layout.

## Features
- PHP web server for local development.
- MariaDB database setup for handling backend data.
- Easy deployment using Docker.
- Modular structure for various data management tasks.

## Prerequisites

Please make sure you have Docker installed on your machine. The entire environment is set up using Docker.

## Installation

Follow the steps below to set up the project:

### 1: Pull the Docker Image
```bash
docker pull node:current-alpine
```

### 2: Run a Docker Container
```bash
docker run -it --name casicam -p 3000:3000 node:current-alpine node
```

### 3: Install Required Packages Inside the Container
```bash
apk add git php php-mysqli mariadb mariadb-client openrc
```

### 4: Clone the Repository
```bash
git clone https://github.com/karkouri-zakaria/casicam.git
```

### 5: Initialize MariaDB
```bash
mariadbd-safe --datadir='/var/lib/mysql'
```
```bash
mariadb-install-db --user=mysql --datadir=/var/lib/mysql
```
```bash
rc-service mariadb start
```
```bash
rc-update add mariadb default
```
```bash
mariadbd-safe --user=root 
```

PS:
```bash
mariadb -u root -p
```

### 6: Navigate to the Project Directory
```bash
cd casicam
```

### 7: Install Dependencies
Install all necessary packages:
```bash
npm install
```

### 8: Build the Project
Compile the Tailwind CSS styles and build the project:
```bash
npm run build
```

### 9: Run the PHP Server
```bash
php -S 0.0.0.0:3000
```

### 10: Run the Project
http://127.0.0.1:3000/index.php

## Usage

After building the project, you can serve the HTML file locally using any static server or by simply opening it in a browser.

## License
This project is licensed under the [MIT License](LICENSE).

---

**Author:** [Karkouri Zakaria](https://github.com/karkouri-zakaria)


```bash
apk add php83-mbstring php83-xml php83-gd php83-iconv php83-ctype php83-zip php83-session
```
