IMAGE   ?= pretty-php:dev
WORKDIR ?= /app

.DEFAULT_GOAL := help

.PHONY: help image shell

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "} {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

image: ## Build the dev image: latest official PHP + ext-sockets + Composer (docker/Dockerfile)
	docker build --pull -t $(IMAGE) docker

# --init: a minimal init is PID 1, so commands get a parent process and are not session leaders
# (POSIX tests check getppid() and setpgid()).
# /etc/passwd and /etc/group of the host give the host UID a user name inside the container.
shell: image ## Interactive shell in the dev image, project mounted into the working directory
	docker run --rm -it --init \
		--user "$$(id -u):$$(id -g)" \
		-v /etc/passwd:/etc/passwd:ro \
		-v /etc/group:/etc/group:ro \
		-v "$(CURDIR)":$(WORKDIR) \
		-w $(WORKDIR) \
		$(IMAGE) bash
