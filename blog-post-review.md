# Running Graph Analytics on Large Scale Graphs Effortlessly with NVIDIA and Memgraph 

## Introduction
With the latest **MAGE** release, you can run **GPU-powered** graph analytics from **Memgraph** in a matter of seconds. Loading a whole dataset containing 1M+ edges and getting first graph analytics results shouldn't take more than 15 seconds :fire:. On top of it all, you can do it in **Python**. Let me show you how.

In this tutorial you will learn:
* how to effortlessly import data inside Memgraph, all while using **Python**
* how to run analytics on graphs and get results fast 
* how to run analytics on **NVIDIA GPU** from Memgraph 

What made all of it possible is the integration and collaboration of **Nvidia cuGraph** and **Memgraph** teams :heart:.

In this tutorial I will show you how to use  **PageRank** graph analysis and **Louvain** community detection on a **Facebook dataset** containing 1.3 million relationships. Upon completing this tutorial, you will know how to run analytics algorithms on your dataset using **Python**. You can run any of the following algorithms: 
* Balanced Cut (clustering)
* Spectral Clustering (clustering) 
* HITS (hubs vs authorities analytics)
* Leiden (community detection)
* Katz Centrality 
* Betweenness Centrality  

All of the algorithms above are powered by **[Nvidia cuGraph](https://www.nvidia.com/en-us/)** and they will execute on **GPU**. 

## Prerequisites

To follow the tutorial, please install:
- [Docker](https://docs.docker.com/get-docker/) - needed to run `mage-cugraph` image
- [Jupyter](https://jupyter.org/install) - using jupyter-notebook you can write  `CSV` importing and graph analytics in one file
- [GQLAlchemy](https://pypi.org/project/gqlalchemy/) - used to connect Memgraph with Python
- [Memgraph Lab](https://memgraph.com/lab) - a GUI tool we use to to visualize graphs

Here are brief instructions on how to install everything you will need.

### Memgraph with Docker
We need *[Docker](https://www.docker.com/)* because Memgraph is a native Linux application and can't be installed on Windows and macOS. In this tutorial we will use mage-cugraph Docker image. Check our [guide](https://memgraph.com/docs/mage) on how to pull the image, and set everything up before running the **Memgraph-cuGraph** container.


Before running `mage-cugraph` Docker image, you should [clone](https://docs.github.com/en/repositories/creating-and-managing-repositories/cloning-a-repository) **[jupyter-memgraph-tutorials](https://github.com/memgraph/jupyter-memgraph-tutorials)** GitHub repo and  position yourself inside `jupyter-memgraph-tutorials/cugraph-analytics` folder. We are ready to start the mage-cugraph image, but let's do a little "hack" in the docker run command that will be useful to us later. 



All the data we need is inside `CSV` files, and Memgraph needs to have access to those files. But because we will run Memgraph within a Docker container and the files are currently on our machine, we need to transfer them inside the same container where the Memgraph will be running. So let's create a Docker volume by mounting our current `data/facebook_clean_data/` folder to the `/samples` folder inside the Docker containers. `CSV` files will be located inside the `/samples` folder within the Docker where *Memgraph* will find them when needed.

Now we can start the `mage-cugraph` image:

```
docker run -it -p 7687:7687 -p 7444:7444 --volume ./data/facebook_clean_data/:/samples mage-cugraph
```
If successful, you should see a message similar to the following:
```
You are running Memgraph vX.X.X
To get started with Memgraph, visit https://memgr.ph/start
```

### Jupyter notebook
Now that the Memgraph is running, install **Jupyter**. For this tutorial, we used **jupyter-lab** and you can install it with the following command:
```
pip install jupyterlab
```

Next, download  **[jupyter-notebook](https://github.com/memgraph/jupyter-memgraph-tutorials/tree/main/cugraph-analytics)** we use in our tutorial. As mentioned before, `CSV` files holding the dataset we will use in the tutorial are located  in the repository folder `cugraph-analytics/data`.

### GQLAlchemy installation
**[GQLAlchemy](https://memgraph.com/docs/gqlalchemy/)** is an object graph mapper (OGM) used to connect to Memgraph and execute queries using **Python**. You can think of Cypher as SQL for graph databases. It contains many of the same language constructs such as CREATE, UPDATE, DELETE, etc.. 

Go to the GQLAlachemy [installation](https://memgraph.com/docs/gqlalchemy/installation) page for installation instructions and more information. If you have `CMake`, the easiest way to install `GQLAlchemy` is with `pip`:

```
pip install gqlalchemy
```

### Memgraph Lab installation
The last piece of tech you will need is **[Memgraph Lab](https://memgraph.com/lab)**.  You will use it to connect to **Memgraph** and create data visualizations. Check out how to install **Memgraph Lab**  as a desktop application for [Windows](https://memgraph.com/docs/memgraph-lab/installation/windows#step-2---installing-and-setting-up-memgraph-lab), [Mac](https://memgraph.com/docs/memgraph-lab/installation/macos#step-2---installing-and-setting-up-memgraph-lab)  or [Linux](https://memgraph.com/docs/memgraph-lab/installation/linux#step-2---installing-and-setting-up-memgraph-lab) and then connect to **[Memgraph database](https://memgraph.com/docs/memgraph-lab/connect-to-memgraph#connecting-to-memgraph)**.

With the **Memgraph Lab** installed and connected, we are ready to connect to Memgraph with **GQLAlchemy**, import our large dataset and run graph analytics using **Python**.

## Connecting to Memgraph with GQLAlchemy

Position yourself in the [jupyter-notebook](https://github.com/memgraph/jupyter-memgraph-tutorials/tree/main/cugraph-analytics). We will start with the `qglalchemy` import and then we will connect to **Memgraph** using `host` and `port`. Afterward, we will clear our database, just in case.

The first three lines of code will import `qglalchemy`, connect to Memgraph database instance via `host:127.0.0.1` and `port:7687`, and clear the database, just to be sure we are starting with a clean slate.


```python
from gqlalchemy import Memgraph
memgraph = Memgraph("127.0.0.1", 7687)
memgraph.drop_database()
```

Let's import the dataset from `CSV` files and learn how to perform  `PageRank` and `Louvain community detection` using **Python**.
## Importing data

The **CSV** files containing [**Facebook** dataset](https://snap.stanford.edu/data/gemsec-Facebook.html) have the following structure:
```
node_1,node_2
0,1794
0,3102
0,16645
```
The dataset consists of verified Facebook pages belonging to different categories and dating back to November 2017.  Nodes are the pages and relationships are mutual likes between them. Nodes represent the pages and relationships are mutual likes among them. The nodes are reindexed (starting from 0) to achieve a certain level of anonymity. Since **Memgraph** imports query faster when data has indexes, we will create them for all the nodes with the label `Page` on the `id` property.  


```python
memgraph.execute(
    """
    CREATE INDEX ON :Page(id);
    """
)
```

Now, to make full use of our "hack" from before, let's list through our local files  in the `./data/facebook_clean_data/` folder to create their paths by concatenating the file names and the `/samples/` folder. Those paths will then represent the paths to the **CSV** files in *Docker* container.




```python
import os
from os import listdir
from os.path import isfile, join
csv_dir_path = os.path.abspath("./data/facebook_clean_data/")
csv_files = [f"/samples/{f}" for f in listdir(csv_dir_path) if isfile(join(csv_dir_path, f))]

```

Once we have all the `CSV` files, we can load them with the following query:

```python
for csv_file_path in csv_files:
    memgraph.execute(
        f"""
        LOAD CSV FROM "{csv_file_path}" WITH HEADER AS row
        MERGE (p1:Page {{id: row.node_1}}) 
        MERGE (p2:Page {{id: row.node_2}}) 
        MERGE (p1)-[:LIKES]->(p2);
        """
    )

```

You can find out more about the `LOAD CSV` clause for importing CSV files in our [docs](https://memgraph.com/docs/memgraph/import-data/load-csv-clause).

We are all set to use PageRank and Louvain community detection algorithms with Python 
to find out which pages in our network are most important and to find all the communities
we have in a network.

## PageRank importance analysis
Now, let's execute PageRank to find important pages of a Facebook dataset.  To read more about how does **Pagerank** works, you can go to our **[docs](https://memgraph.com/docs/mage/query-modules/cpp/pagerank)** page. All algorithms mentioned in [introduction](#introduction) developed by **[Nvidia](https://rapids.ai/)**, and they are now integrated within **MAGE - Memgraph Advanced Graph Extensions** . Our goal in **Memgraph** is to make it easy for you to use algorithms on graph databases and get results quickly. 

Graph algorithms in MAGE are implemented in C++ or Python. MAGE also includes many other graph analytics algorithms, from **graph neural networks** to many different centralities. You can learn more about them on the **MAGE** [docs](https://memgraph.com/docs/mage/algorithms) page, and we've also written many tutorials on how to use such analytics to to classify nodes, predict relationships, and more, so do not hesitate to check out our tutorials as well. Check out our [tutorials](https://memgraph.com/categories/tutorials) for more examples.

Everything inside `MAGE` is integrated in a way to make executing PageRank really easy.By running the following line, you will get results in ~4 seconds for our huge graph. The following query will first execute the algorithm, and then create and set the `rank` property
of each node to the value that the `cugraph.pagerank` algorithm returned. The value of that property will then be saved as a variable `rank`. This whole query will be executed in ~4 seconds for our graph of more than 1 million edges.


```python
  memgraph.execute(
        """
        CALL cugraph.pagerank.get() YIELD node,rank
        SET node.rank = rank;
        """
    )
```

Now, ranks are ready and you can retrieve them with the following Python call:


```python
results =  memgraph.execute_and_fetch(
        """
        MATCH (n)
        RETURN n.id as node, n.rank as rank
        ORDER BY rank DESC
        LIMIT 10;
        """
    )
for dict_result in results:
    print(f"node id: {dict_result['node']}, rank: {dict_result['rank']}")
```

    node id: 50493, rank: 0.0030278728385218327
    node id: 31456, rank: 0.0027350282311318468
    node id: 50150, rank: 0.0025153975342989345
    node id: 48099, rank: 0.0023413620866201052
    node id: 49956, rank: 0.0020696403564964
    node id: 23866, rank: 0.001955167533390466
    node id: 50442, rank: 0.0019417018181751462
    node id: 49609, rank: 0.0018211204462452515
    node id: 50272, rank: 0.0018123518843272954
    node id: 49676, rank: 0.0014821440895415787


In the code above, we returned 10 nodes with the highest rank score. Results are available in dictionary form. Now it is time to visualize results with **[Memgraph Lab](https://memgraph.com/lab)**. Besides creating beautiful visualizations powered by [D3.js](https://d3js.org/) and our[graph style script](https://memgraph.com/docs/memgraph-lab/graph-style-script-language), you can use **Memgraph Lab** to [query graph database](https://memgraph.com/docs/memgraph-lab/connect-to-memgraph#executing-queries) and write your graph algorithms in **Python or C++ or even Rust**, check Memgraph Database Logs, visualize graph schema. If you don't have your own dataset on hand, there are plenty of datasets available in Memgraph Lab that you can explore. Everything you might need to know about Memgraph Lab can be found in our [docs](https://memgraph.com/docs/memgraph-lab/).

Open `Execute Query` view in **Memgraph Lab** and run the following query:
```
MATCH (n)
WITH n
ORDER BY n.rank DESC
LIMIT 3
MATCH (n)<-[e]-(m)
RETURN *;
```

Here in the query first we will `MATCH` all the nodes. In the second part of the query we will `ORDER` nodes by their  `rank` in descending order, and for the first `3` of them get all pages connected to them. We need the `WITH` clause to connect the two parts of the query inside our one query.

![](https://hackmd.io/_uploads/r1mQRhz85.png)


Now, that's it considering PageRank, next you will see how to use Louvain community detection to find communities in the graph. 

## Community detection with Louvain 
The Louvain algorithm measures how connected are the nodes within a community if we would compare them to how connected they would be in a random network. Also, it recursively merges communities into a single node and executes the modularity clustering on the condensed graphs. This is one of the most popular community detection algorithms. Let's run it to find how many communities there are inside the graph. First, we will let Louvain execute and save `cluster_id` as a property for every node. 


```python
memgraph.execute(
    """
    CALL cugraph.louvain.get() YIELD cluster_id, node
    SET node.cluster_id = cluster_id;
    """
)
```

Now, let us find the number of communities:

```python
results =  memgraph.execute_and_fetch(
        """
        MATCH (n)
        WITH DISTINCT n.cluster_id as cluster_id
        RETURN count(cluster_id ) as num_of_clusters;
        """
    )
# we will get only 1 result
result = list(results)[0]

#don't forget that results are saved in a dict
print(f"Number of clusters: {result['num_of_clusters']}")

```

    Number of clusters: 2664


Next, you can also visualize some of these communities. You can, for example, find nodes that belong to one community, but are connected to another node that belongs in the opposing community. As for Louvain, it tries to minimize that number of nodes, so we shouldn't see that many of them. In Memgraph Lab try to execute the following query:

```
MATCH  (n2)<-[e1]-(n1)-[e]->(m1)
WHERE n1.cluster_id != m1.cluster_id AND n1.cluster_id = n2.cluster_id
RETURN *
LIMIT 1000;
```
This query will MATCH node `n1` and its relationship to other two nodes `n2` and `m1` with 
following parts respectively:`(n2)<-[e1]-(n1)` and `(n1)-[e]->(m1)`. Then, it will filter out 
only those nodes  `WHERE` cluster_id of `n1` and `n2` is not the same as the  cluster_id of node `m1`.
Using `LIMIT 1000` we limit the results to show only 1000 of such relationships, for easier visualizations.

In Memgraph Lab you can also style your graphs with [Graph Style Script](https://memgraph.com/docs/memgraph-lab/graph-style-script-language) so we used different colors to represent different communities. From the query above we got the following visualization:

![](https://hackmd.io/_uploads/Syc4weEIq.png)




## Where to next?
And there you have it - millions of nodes imported and two graph analytics algorithms used. 
Now you can import huge graphs and do the analytics you want in a matter of seconds. 
If you like what we do, don't hesitate to give us a star on  **[Memgraph](https://github.com/memgraph/memgraph)**, **[Memgraph MAGE](https://github.com/memgraph/mage)** and also don't forget to give a star to devs in **[Rapids.ai cuGraph](https://github.com/rapidsai/cugraph)** . :star:

If you have any questions, feel free to ask us Memgraphers on **[Discord](https://discord.gg/memgraph)**. 


Onwards and upwards :rocket: 
