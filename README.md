# Cloudflare tools

Console commands for Cloudflare actions

## Installation

1. Create an API Token on Cloudflare

While logged to Cloudflare dashboard, go to **__Profile__ > __API Tokens__ > __Create Token__**.

Your token must be granted with **Zone DNS Edit** permission on your desired zones.

2. Paste the token in the .env file
```
CLOUDFLARE_API_TOKEN=abc123
```

3. Build the docker container
```shell
make build
```

## Usage

### Run commands
```shell
make run COMMAND="cloudflare:zones:list"
make run COMMAND="cloudflare:dns:list"
make run COMMAND="cloudflare:dns:import"
```

### View available commands
```shell
make run COMMAND="list"
```

### View commands detailed description

Run commands with ```--help``` option to view detailed description
```shell
make run COMMAND="cloudflare:dns:import --help"
```
