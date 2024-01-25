all: up

up:
	docker-compose -f docker/docker-compose.yml up --build -d
	@echo "Done!"
	@echo "http://0.0.0.0:8080"

down:
	docker-compose -f docker/docker-compose.yml down
	@echo "Done!"