# RMS - Backend

This project is an API built using Laravel. Below are instructions to set up the project locally using various development environments.

## Prerequisites

- PHP 8.2
- Composer
- Database (MySQL or MariaDB)
- Docker and Docker Compose

---

## Local Setup

You have multiple options for setting up the project locally. Choose the one that suits your environment best.

### Option 1: Docker (recommended)

1. Ensure Docker is installed on your machine.
2. Clone the repository:
   ```bash
   git clone git@github.com:MarkVilludo/ipam-auth-service.git
   cd ipam-auth-service
   ```
3. Copy `docker/local/.env.local` to `site/.env` and configure as needed (optional: if omitted, the container uses a default at first run).
4. Append to your local hosts file (`/etc/hosts` on macOS/Linux):
   ```text
   127.0.0.1 api-auth-service.local.com
   ```
   Then use the API **with the port**: **http://api-auth-service.local.com:8080** or **https://api-auth-service.local.com:8443** (do not omit `:8080` / `:8443`).
5. Create local certificate (mkcert):

   ```text
   cd docker/local/nginx/mkcert

   // Note: Delete existing certificate files before creating new cert
   mkcert api-auth-service.local.com
   ```

6. Build and run the Docker containers:
   ```bash
   docker compose -f docker-compose.local.yml up --build -d
   ```
   Access the API at **http://localhost:8080**, **https://localhost:8443**, **http://api-auth-service.local.com:8080**, or **https://api-auth-service.local.com:8443**. You must include the port (`:8080` or `:8443`).
7. Generate new app key:
   ```bash
   docker exec api-auth-service php artisan key:generate
   ```
8. Run the migrations and seed the database:
   ```bash
   docker exec api-auth-service php artisan migrate
   docker exec api-auth-service php artisan db:seed
   ```
9. **Connect to MySQL from your machine** (TablePlus, DBeaver, CLI): use **port 3308** (not 3306). The container maps `3308:3306`.
   - Host: `127.0.0.1`
   - Port: **3308**
   - User: `root`
   - Password: `secret`
   - Database: `auth_service`
10. Use postman collection to test the app.
   - TBA

### Option 2: Laragon

### Option 3: Herd
