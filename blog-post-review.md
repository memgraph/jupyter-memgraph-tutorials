# Running Graph Analytics on Large Scale Graphs Effortlessly with NVIDIA and Memgraph 

## Introduction
Since **MAGE's** latest release, you can now run **GPU** powered graph analytics from **Memgraph** in seconds. Loading the whole dataset containing 1M+ edges and getting the first graph analytics results shouldn't take more than 15 seconds :fire: On top of it all, you can do it in **Python**. Let me show you how.

In this tutorial you will learn:
* how to effortlessly import data inside Memgraph, all while using **Python**
* how to run analytics on graphs and get results fast 
* how to run analytics on **NVIDIA GPU** from Memgraph 

What made all of it possible is the integration and collaboration of **Nvidia cuGraph** and **Memgraph** teams :heart:.

In this tutorial I will show you how to use  **PageRank** graph analysis and **Louvain** community detection on a **Facebook dataset** containing 1.3 million edges. After you follow this tutorial, you will know how to run  any of the following graph analytics algorithms with **Python** on your dataset:  
* Balanced Cut (clustering)
* Spectral Clustering (clustering) 
* HITS (hubs vs authorities analytics)
* Leiden (community detection)
* Katz Centrality 
* Betweenness Centrality  

All above algorithms are powered by **[Nvidia cuGraph](https://www.nvidia.com/en-us/)** and they will execute on **GPU**. 

## Prerequisites

To follow the tutorial, please install:
- [Docker](https://docs.docker.com/get-docker/) - needed to run `mage-cugraph` image
- [Jupyter](https://jupyter.org/install) - using jupyter-notebook you can write  `CSV` importing and graph analytics in one file
- [GQLAlchemy](https://pypi.org/project/gqlalchemy/) - to connect to Memgraph with Python
- [Memgraph Lab](https://memgraph.com/lab) - a GUI tool we use to to visualize graphs


### Memgraph with Docker
We use *[Docker](https://www.docker.com/)* because the Memgraph database is a native Linux application and cannot be installed on Windows and macOS. But for this part, we will use **Docker image** of  `mage-cugraph`. [Here](https://memgraph.com/docs/mage) you can check how to pull the image, and set everything up before running the **Memgraph-cuGraph** container.


Before running `mage-cugraph` Docker image, you should [clone](https://docs.github.com/en/repositories/creating-and-managing-repositories/cloning-a-repository) **[jupyter-memgraph-tutorials](https://github.com/memgraph/jupyter-memgraph-tutorials)** GitHub repo and position yourself inside `jupyter-memgraph-tutorials/cugraph-analytics` folder. We will do one "hack" here for later. Since we are working with *Docker* and we will read our **CSV** files, **Memgraph** needs to have access to such files. But they are currently on our machine and need to be inside *Docker*. That's we will create **Docker** volume mounting our current `data/facebook_clean_data/` folder to `/samples` folder inside the Docker container. Inside `/samples` folder, *Memgraph* will find our **CSV** files.

After you cloned the repo and positioned yourself in it, now we can start the `mage-cugraph` image:

```
docker run -it -p 7687:7687 -p 7444:7444 --volume ./data/facebook_clean_data/:/samples mage-cugraph
```


### Jupyter notebook
After you have Memgraph running, you should install **Jupyter**. For this tutorial, we used **jupyter-lab** and you can install it with the following command:
```
pip install jupyterlab
```

Now you can go and download **[jupyter-notebook](https://github.com/memgraph/jupyter-memgraph-tutorials/tree/main/cugraph-analytics)** we use in our tutorial. Note, in a repository folder `cugraph-analytics/data` there are `CSV` files containing the full dataset which we will use later in the tutorial.


### GQLAlchemy installation
We will be using the **[GQLAlchemy](https://memgraph.com/docs/gqlalchemy/)** object graph mapper (OGM) to connect to Memgraph and execute **Cypher** queries from **Python**. You can think of Cypher as SQL for graph databases. It contains many of the same language constructs like `CREATE`, `UPDATE`, `DELETE`... and it's used to query the database. GQLAlchemy serves as a Python driver/client for Memgraph. Go to our [installation](https://memgraph.com/docs/gqlalchemy/installation) page to check how more options on how to install it. If you wish, you can install it with `pip`, but be sure also to have `CMake` installed. After you have `CMake` ready, you can install `GQLAlchemy` with the following command:
```
pip install gqlalchemy
```

### Memgraph Lab installation
Last piece of tech you will use is **[Memgraph Lab]()**. You will use it to connect to **Memgraph** and create visualizations. To check how to install **Memgraph Lab** and connect to **Memgraph**, you can go to one of the following pages for installation instructions for **Memgraph Lab as desktop application** for [Windows](https://memgraph.com/docs/memgraph-lab/installation/windows#step-2---installing-and-setting-up-memgraph-lab), [Mac](https://memgraph.com/docs/memgraph-lab/installation/macos#step-2---installing-and-setting-up-memgraph-lab) or [Linux](https://memgraph.com/docs/memgraph-lab/installation/linux#step-2---installing-and-setting-up-memgraph-lab). After you have installed **Memgraph Lab**, you can check instructions how to **[connect to Memgraph](https://memgraph.com/docs/memgraph-lab/connect-to-memgraph#connecting-to-memgraph)** with Memgraph Lab. 


Now, when you have installed everything, we continue to the main topic of the tutorial: **importing large dataset** and **running graph analytics** with **Python**.

## Connecting to Memgraph with GQLAlchemy

Now position yourself in [jupyter-notebook](https://github.com/memgraph/jupyter-memgraph-tutorials/tree/main/cugraph-analytics) you previously downloaded. We will start with the `qglalchemy` import and then we will connect to **Memgraph** using `host` and `port`. Afterward, we will clear our database, just in case.


```python
from gqlalchemy import Memgraph
```


```python
memgraph = Memgraph("127.0.0.1", 7687)
```


```python
memgraph.drop_database()
```

In the next few steps we will do the following:
* we will show you how to import a large dataset from the **CSV** file inside **Memgraph** in terms of seconds. 
* then you will see how to perform `PageRank` and `Louvain community detection` all by using **Python**

## Importing data

**CSV** files containing [**Facebook** dataset](https://snap.stanford.edu/data/gemsec-Facebook.html) have the following structure:
```
node_1,node_2
0,1794
0,3102
0,16645
```
Dataset consists of Facebook pages (from November 2017). It represents a network of verified Facebook pages of different categories. Nodes represent the pages and relationships are mutual likes among them. The nodes are reindexed (starting from 0) to achieve a certain level of anonymity. Since **Memgraph** imports query faster when it has indexes, we will create one for all nodes with the label `Page` on the `id` property.  


```python
memgraph.execute(
    """
    CREATE INDEX ON :Page(id);
    """
)
```

Now we will make *full* usage of our "hack" from before. We will list through our local files in the `./data/facebook_clean_data/` folder and create the path from them concatenating our file names to the `/samples/` folder which will then represent the path to **CSV** files in *Docker* container.



```python
import os
from os import listdir
from os.path import isfile, join
csv_dir_path = os.path.abspath("./data/facebook_clean_data/")
csv_files = [f"/samples/{f}" for f in listdir(csv_dir_path) if isfile(join(csv_dir_path, f))]

```

Once we have all the `CSV` files, we will load all of them with the following query:

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

You can find out more about the `LOAD CSV` clause importing CSV files in our [docs](https://memgraph.com/docs/memgraph/import-data/load-csv-clause).

We are all set to use PageRank and Louvain community detection algorithms with Python to find out which pages in our network are most important and to find all the communities we have in a network.

## PageRank importance analysis
Now, we will execute PageRank to find important pages of a Facebook dataset. To read more about how does **Pagerank** works, you can go to our **[docs](https://memgraph.com/docs/mage/query-modules/cpp/pagerank)** page. All algorithms mentioned in [introduction](#introduction) part are developed by **[Nvidia](https://rapids.ai/)** and now are integrated under **MAGE - Memgraph Advanced Graph Extensions** . Our goal in **Memgraph** is for you to have it very easy when it comes to using algorithms on graph databases and getting results fast. They are implemented in C++ or Python. There is a lot of other graph analytics available for you under **MAGE**, from **graph neural networks** all to different centralities. You can learn more about them [here](https://memgraph.com/docs/mage/algorithms) and we have a series of tutorials you can read to learn how to use such analytics to classify nodes, predict relationships, and more. Check out our [tutorials](https://memgraph.com/categories/tutorials) for more examples.

Back to PageRank. Everything inside `MAGE` is integrated for you to execute PageRank with ease. By running the following line, you will get results in ~4 seconds for our huge graph. In the other part of the query, we will create and set the `rank` property of the node to a value that the `cugraph.pagerank` algorithm returned under the variable `rank` for every `node`.


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


Here we only returned 10 nodes with the highest rank score. Results are available in dictionary form. Now it is time to visualize results with **[Memgraph Lab](https://memgraph.com/lab)**. Besides creating beautiful visualizations powered by [D3.js](https://d3js.org/) and our [graph style script](https://memgraph.com/docs/memgraph-lab/graph-style-script-language), you can use **Memgraph Lab** to [query graph database]() and write your graph algorithms in **Python or C++ or even Rust**, check Memgraph Database Logs, visualize graph schema. If you don't have any idea on which dataset you can do it, there are plenty of datasets available for you with **Memgraph Lab** to start and explore. You can learn more about Memgraph Lab [here](https://memgraph.com/docs/memgraph-lab/).

Now, let's find the top 3 Pages and visualize their relationships in the graph. We will use the following query in Memgraph Lab:
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
The Louvain algorithm measures how connected are the nodes within a community if we would compare them to how connected they would be in a random network. Also, it recursively merges communities into a single node and executes the modularity clustering on the condensed graphs. This is one of the most popular community detection algorithms. Let's run it to find how many communities we have an inside graph. First, we will let Louvain execute and save `cluster_id` as a property for every node. 


```python
memgraph.execute(
    """
    CALL cugraph.louvain.get() YIELD cluster_id, node
    SET node.cluster_id = cluster_id;
    """
)
```

Now, let us find number of communities:

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


Next, you can also visualize some of these communities. You can for example find nodes that belong to one community, but are connected to another node that belongs in the opposing community.
As for Louvain, it tries to minimize that number of nodes, so we shouldn't see that many of them. In Memgraph Lab try to execute the following query:

```
MATCH  (n2)<-[e1]-
WHERE n1.cluster_id != m1.cluster_id AND n1.cluster_id = n2.cluster_id
RETURN *
LIMIT 1000;
```
Here in our query we will `MATCH`  node `n1` and its two relationships `(n2)<-[e1]-(n1)` and `(n1)-[e]->(m1)` but we will filter out only ones `WHERE` cluster_id of nodes `n1` and `n2` is not the same as the  cluster_id of node `m1`. We will return only 1000 such relationships, for easier visualizations.

We used different functions for the `red`, `green`, and `blue` (RGB) color model to create such visualization. From the query above we got the following visualization:

![](https://hackmd.io/_uploads/Syc4weEIq.png)




## Where to next?
And there you have it - millions of nodes imported and two graph analytics algorithms used. Now you can import huge graphs and do the analytics you want in terms of seconds. Most of all I hope we made those two parts a little easier for you :heart:. If you feel so, don't forget to give us a star on **[Memgraph](https://github.com/memgraph/memgraph)**, **[Memgraph MAGE](https://github.com/memgraph/mage)** and also don't forget to give a star to devs in **[Rapids.ai cuGraph](https://github.com/rapidsai/cugraph)** . :star:

If you have any questions, feel free to ask us Memgraphers on a **[Discord](https://discord.gg/memgraph)**. 


Onwards and upwards :rocket: 
