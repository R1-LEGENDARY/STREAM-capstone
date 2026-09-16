# STREAM

STREAM: Smart Technology for Real-Time Environmental Aquatic Monitoring is a Laravel-based aquatic monitoring dashboard for viewing real-time environmental readings such as dissolved oxygen, pH, and temperature. It is paired with an Arduino-based sensor station that collects readings and posts them to the backend.

## Project overview

- Backend: Laravel 12
- Frontend: Blade + Vite + vanilla JavaScript
- Database: MySQL
- Main dashboard: `/dashboard`
- Welcome page: `/`
- Summary page: `/summary`
- Arduino firmware: `Arduino/MAINCODE/stream_aquatic_monitoring_saleng128db_v1_0_4/stream_aquatic_monitoring_saleng128db_v1_0_4.ino`

## Requirements

Before running the project, install the following:

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- MySQL or MariaDB
- XAMPP (optional, if you want the Apache + MySQL stack locally)
- Arduino IDE (for firmware upload)

## Setup

### 1) Open the project folder

Clone the repository and open the project directory:

```bash
git clone https://github.com/R1-LEGENDARY/STREAM-capstone.git
cd STREAM-capstone
```

### 2) Install PHP dependencies

```bash
composer install
```

### 3) Install frontend dependencies

```bash
npm install
```

### 4) Configure the environment

Copy the example file:

```bash
copy .env.example .env
```

Then update the database values in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stream
DB_USERNAME=root
DB_PASSWORD=
```

If needed, also update:

```env
APP_URL=http://127.0.0.1:8000
```

### 5) Generate the app key

```bash
php artisan key:generate
```

### 6) Create the database

In MySQL, create a database named `stream`.

### 7) Run database migrations

```bash
php artisan migrate
```

## Run the app

### Option A: Laravel development server

```bash
php artisan serve
```

Then open:

```text
http://127.0.0.1:8000/
```

### Option B: XAMPP / local web server

You can also run this project through XAMPP by placing the project in the web root and pointing Apache to the `stream/public` folder.

Example:

```text
http://localhost/stream/public/
```

If you use XAMPP, make sure the MySQL service is started and the database is created before running the app.

### Frontend asset build

For Vite assets during development:

```bash
npm run dev
```

For production build:

```bash
npm run build
```

## Usage

Once the app is running:

- Visit `/` to open the welcome page
- Visit `/dashboard` to view monitoring data
- Visit `/summary` to open the summary page

The dashboard loads readings from the `readings` table and can filter by:

- today
- last 7 days
- last 30 days
- custom date/time range

## Data flow

The system works like this:

1. The Arduino sensor station reads DO, pH, and temperature.
2. It connects to WiFi through the ESP-01 module.
3. It sends JSON data to the configured backend server.
4. Laravel stores the records in the `readings` table.
5. The dashboard reads and displays the data.

## Arduino setup

The main firmware is located here:

```text
Arduino/MAINCODE/stream_aquatic_monitoring_saleng128db_v1_0_4/stream_aquatic_monitoring_saleng128db_v1_0_4.ino
```

### Required Arduino libraries

Install these inside the Arduino IDE libraries folder:

- `LiquidCrystal_I2C`
- `OneWireNg_CurrentPlatform`
- `DSTherm`

### Important values to update in the firmware

Open the `.ino` file and update the following in the configuration section:

```cpp
const char* WIFI_SSID = "YOUR_WIFI_NAME";
const char* WIFI_PASSWORD = "YOUR_WIFI_PASSWORD";
const char* POST_PATH = "/";
const char* SERVER_NAME = "YOUR_SERVER_IP";
const int SERVER_PORT = 5000;
```

Set these values to match your local WiFi and backend server. Use the local IP address of the computer running Laravel for `SERVER_NAME`. The computer running Laravel and the Arduino must be connected to the same network.

### Upload steps

1. Open the `.ino` sketch in Arduino IDE.
2. Select the correct board and COM port.
3. Verify and upload the firmware.
4. Power the board and wait for the WiFi connection.
5. Check the serial monitor for connection and data upload status.

## Hardware and device setup

This project uses a sensor station built around an Arduino-compatible board and a WiFi module.

### Main device components

- Arduino board or compatible MCU
- ESP-01 WiFi module for TCP communication
- 16x2 I2C LCD display
- DS18B20 temperature sensor
- pH probe with analog interface
- Dissolved oxygen probe with analog signal conditioning
- Buzzer and status LEDs
- Push button for manual upload trigger

### Main sensor roles

- Temperature sensor: DS18B20
- pH sensor: analog pH probe with ADC calibration
- Dissolved oxygen sensor: analog DO probe with calibration table
- Display: I2C LCD 16x2
- Communication: ESP-01 serial bridge to the Laravel TCP server

### TCP server command

The backend includes a built-in TCP listener for Arduino data:

```bash
php artisan tcp:serve
```

This runs the socket server on the address and port configured in `.env`:

```text
TCP_SERVER_ADDRESS=0.0.0.0
TCP_SERVER_PORT=5000
```

The Arduino connects to the Laravel computer using the computer's local network IP in `SERVER_NAME`.

The command receives JSON payloads like this:

```json
{"TS":"...","DO":6.80,"pH":7.12,"Temperature":27.30}
```

The Laravel app stores these values into the `readings` table.

## Useful commands

```bash
php artisan migrate
php artisan serve
npm install
npm run dev
npm run build
php artisan test
```

## Troubleshooting

### App not loading

- Check that MySQL is running
- Confirm `.env` values are correct
- Make sure Composer dependencies are installed

### Database errors

- Verify the database exists
- Check the DB username/password in `.env`
- Run:

```bash
php artisan migrate:fresh
```

### Arduino cannot connect

- Confirm the WiFi credentials are correct
- Verify the board is on the same network
- Confirm the server IP and port are reachable
- Check the serial output for ESP-01 status messages
