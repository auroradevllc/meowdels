[![License: ISC](https://img.shields.io/badge/License-ISC-blue.svg)](https://opensource.org/licenses/ISC)
[![Stability: Work in Progress](https://img.shields.io/badge/Stability-Work%20in%20Progress-orange.svg)](https://github.com/auroradevllc/meowdels)
[![Platform: amd64 | arm64](https://img.shields.io/badge/Platform-amd64%20|%20arm64-lightgrey.svg)](https://ghcr.io/auroradevllc/meowdels)

Meowdels is a Laravel-based application for 3D model management. This project is currently under active development.

The goal is to provide a simple place for you to store all the 3D model files you are actively wanting to print/use, 
including ones from other sites, and be able to organize them as you like.

For a glimpse of what the UI looks like, see the [screenshots](screenshots) folder.

## Defaults/Configuration

By default, this application will use `sqlite` for the database, local storage for files, and other laravel defaults.

This can be configured with anything from MySQL to Postgres for the database, anything Laravel supports.

For caching/sessions, Redis/Memcached can be used.

For file storage, this project uses [Spatie Media Library](https://spatie.be/docs/laravel-medialibrary/v11/introduction)
which supports many different filesystems, anything that Laravel supports. S3 is a popular choice.

## Docker

This project is provided as an all-in-one Docker image, containing PHP and a webserver.

To use it, see [docker-compose.yml](docker-compose.yml) or use the image directly:

```
docker run -d -v ./storage/app:/var/www/storage/app --name meowdels -p 80:80 --env-file=.env ghcr.io/auroradevllc/meowdels:latest
```

## Manual Installation

Follow these steps to get your local environment set up. This will allow you to develop/modify this application.

1. **Clone the repository:**
   ```bash
   git clone [https://github.com/auroradevllc/meowdels.git](https://github.com/auroradevllc/meowdels.git)
   cd meowdels
   ```
2. **Install PHP Dependencies**
    ```bash
    composer install
   ```
3. **Install and Build Frontend**
    ```bash
    npm install
    npm run build
    ```
4. **Configure env file**
   1. Copy `.env.example` to `.env`
   2. Configure appropriate settings

5. **Run database migrations and seeders**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```
    The seeder creates a default admin account:

    Email: admin@example.com

    Password: password

6. **Link your storage directory**
    ```bash
    php artisan storage:link
   ```

7. **Start local development serrver**
    ```bash
    composer run dev
    ```

## TODO

- Fix 3MF files with extensions on the previewer (see [lib3mf-cli-docker](https://github.com/aurroradevllc/lib3mf-cli-docker))
- Add more importers (Thangs, etc)
- Make the UI more clear and easy to use
- Improve model preview generation, allow server-side generation?

## License

This project is licensed under the ISC License (see LICENSE file).
