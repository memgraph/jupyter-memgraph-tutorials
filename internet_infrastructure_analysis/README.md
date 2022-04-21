# Explore the Global Shipping Network

This Jupyter notebook accompanies [Analyze Infrastructure Networks with Dynamic Betweenness Centrality](https://hackmd.io/BNXZgIjxREGb07LisUf0Fg).

## Prerequisites 

In this tutorial, you will use Memgraph with:
* [**Docker**](https://hub.docker.com/r/memgraph/memgraph-platform), 
* [**gqlalchemy**](https://pypi.org/project/gqlalchemy/),
* and [**Jupyter Notebook**](https://jupyter.org/install) installed.

## Data

The dataset used in this blogpost represents the global network 
of submarine internet cables in the form of a graph whose nodes
stand for landing points (hubs where cables connect), the cables
connecting them represented as edges.

Landing points and cables have unique identifiers (`id`), and the
landing points also have names (`name`).

The dataset is modified from [one](https://github.com/telegeography/www.submarinecablemap.com)
made by [TeleGeography](https://www2.telegeography.com/) for their 
[submarine cable map](https://www.submarinecablemap.com).

## Run It

1. Clone the repository
2. There needs to be available a running instance of Memgraph, start it like this:
    ```bash
    docker run -it -p 7687:7687 -p 3000:3000 memgraph/memgraph-platform
    ```
3. Start Jupyter Notebook and open `dynamic_betweenness_centrality.ipynb`
