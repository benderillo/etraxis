.PHONY: build run

VERSION ?= $(or $(shell git describe --match 'v*' --dirty),\
  $(error No previous tag found! Please tag the first commit with v0.0.0))

IMAGE_NAME := etraxis
DOCKER_IMAGE := $(IMAGE_NAME):$(VERSION)

build: Dockerfile
	docker image build \
	  --tag $(DOCKER_IMAGE) \
	  --tag $(IMAGE_NAME):latest \
	  -f $< .

run: build
	docker run --rm \
		--user $$(id -u):$$(id -g) \
		--p 8080:8080 \
	  $(DOCKER_IMAGE)

clean:
	docker rmi -f $$(docker images -q $(IMAGE_NAME))


