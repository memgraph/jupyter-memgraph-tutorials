# Game of Graphs - Graph Analytics on a GoT Dataset

Graph analytics workshop on a Game of Thrones dataset can be found in Jupyter Notebook [game-of-graph.ipynb](./game-of-graphs.ipynb). To run it, [install Jupyter Notebook](https://jupyter.org/install#jupyter-notebook) and [prerequisites](#prerequisites) defined below. 

## Prerequisites <a name="prerequisites"></a>

For this tutorial, we need to install:

- [Docker](https://docs.docker.com/get-docker/) - to run Memgraph, since Memgraph is a native Linux application and cannot be installed on Windows and macOS
- [Memgraph Platform](https://memgraph.com/docs/memgraph/installation) - the complete streaming graph application platform; follow the instructions to install Memgraph Platform with Docker for your OS
- [GQLAlchemy](https://pypi.org/project/gqlalchemy/)

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

We will use the **GQLAlchemy**'s object graph mapper (OGM) to connect to Memgraph and quickly execute **Cypher** queries. GQLAlchemy also serves as a Python driver/client for Memgraph. We can install it using:

```
pip install gqlalchemy==1.4.1
```

> You may be missing some of the prerequisites for GQLAlchemy, so make sure to [install them](https://memgraph.com/docs/gqlalchemy/installation) beforehand. 
