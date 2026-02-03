# Auth Service

Authentication and authorization microservice for the IP Address Management (IPAM) system. It issues JWT tokens for the gateway and frontend, manages user registration and login, and enforces roles (e.g. `user`, `super_admin`) via Spatie Permission so other services (e.g. IP management) can authorize requests.

**Purpose:** Single place for user identity and JWT issuance; the gateway and IP service rely on this service for authenticated API access and role-based access control.

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
4. Append on your local hosts:
   ```text
   127.0.0.1 api-auth-service.local.com
   ```
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
   Access the API at **https://api-auth-service.local.com:8443**. The port is required when running multiple containers (auth, IP management, gateway, etc.) so each stack uses different host ports and does not conflict. To use a URL without a port, run a single reverse proxy (e.g. gateway) on 80/443 that routes by hostname to each service.
7. Generate new app key:
   ```bash
   docker exec api-auth-service php artisan key:generate
   ```
8. Run the migrations and seed the database:
   ```bash
   docker exec api-auth-service php artisan migrate
   docker exec api-auth-service php artisan db:seed
   ```
9. Use postman collection to test the app.
   - TBA
