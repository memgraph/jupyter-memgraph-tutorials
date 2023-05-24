# Game of Graphs - Graph Analytics on a GoT Dataset in PHP

## Prerequisites <a name="prerequisites"></a>

- [Docker](https://docs.docker.com/get-docker/) - to run Memgraph, since Memgraph is a native Linux application and cannot be installed on Windows and macOS
- [Memgraph Platform](https://memgraph.com/docs/memgraph/installation) - the complete streaming graph application platform; follow the instructions to install Memgraph Platform with Docker for your OS

> ### Memgraph Platform installation using Docker
>
> After we install Docker, we can run the Memgraph Platform container by running:
>
>```
>docker run -it -p 7687:7687 -p 7444:7444 -p 3000:3000 memgraph/memgraph-platform:2.7.1-memgraph2.7.0-lab2.6.0-mage1.7.0
>```
>
>**Memgraph Platform** contains:
>
>- **MemgraphDB** - the database that holds your data
>- **Memgraph Lab** - visual user interface for running queries and visualizing graph data (running at >`localhost:3000`)
>- **mgconsole** - command-line interface for running queries
>- **MAGE** - graph algorithms and modules library

We will use **Bolt PHP client** to connect to Memgraph and quickly execute **Cypher** queries. 

## Run the app

1. Download `index.php` locally and position yourself in the folder containing the script.
2. Run a composer command to get the required library
```
composer require stefanak-michal/memgraph-bolt-wrapper
```
3. Start the application from the terminal with the following command:
```
php -S localhost:4000
```
4. Head over to the localhost:4000 and see the results. To explore the dataset further, inspect the code and run queries in Memgraph Lab.
