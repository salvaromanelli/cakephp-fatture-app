# CakePHP Fatture App

A CakePHP application for invoice management (fatture) running in Docker containers.

## Requirements

- Docker Desktop
- Docker Compose

## Getting Started

1. **Start Docker Desktop**
   Make sure Docker Desktop is running on your Mac.

2. **Build and start the containers:**
   ```bash
   docker-compose up -d --build
   ```

3. **Access the application:**
   - Web Application: http://localhost:8080
   - MySQL Database: localhost:3307

## Database Configuration

- **Database**: `fatture`
- **Username**: `cakeuser`
- **Password**: `cakepass`
- **Host**: `db` (inside Docker network) or `localhost` (from host machine)
- **Port**: 3306 (inside Docker) or 3307 (from host machine)

## Project Structure

```
├── app/                # CakePHP application
├── docker-compose.yml  # Docker services configuration
├── dockerfile          # PHP/Apache container definition
└── README.md           # This file
```

## Useful Commands

### Docker Commands
```bash
# Start containers
docker-compose up -d

# Stop containers
docker-compose down

# View logs
docker-compose logs app

# Rebuild containers
docker-compose up -d --build

# Access container shell
docker-compose exec app bash
```

### CakePHP Commands (inside container)
```bash
# Access the container
docker-compose exec app bash

# Run migrations (when you create them)
bin/cake migrations migrate

# Generate models, controllers, etc.
bin/cake bake model Invoice
bin/cake bake controller Invoice
```

### Development

The `app/` directory is mounted as a volume, so any changes you make to your CakePHP code will be immediately reflected in the running container.

### Database Access

You can connect to the MySQL database using any MySQL client:
- Host: localhost
- Port: 3307
- Username: cakeuser
- Password: cakepass
- Database: fatture

## Next Steps

1. Start developing your invoice management features
2. Create database tables using CakePHP migrations
3. Build models, controllers, and views for your invoice system
4. Configure authentication and authorization as needed

## Troubleshooting

If you encounter any issues:

1. Make sure Docker Desktop is running
2. Check container status: `docker-compose ps`
3. View logs: `docker-compose logs`
4. Restart containers: `docker-compose restart`