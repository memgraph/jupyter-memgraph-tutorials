# Run Graph Analytics on Large Scale Graphs with NVIDIA and Memgraph 
This tutorial will show you how to use  **PageRank** graph analysis and **Louvain** community detection on a **Facebook dataset** containing 1.3 million relationships. 
Upon completing it, you will know how to run analytics algorithms on your dataset using **Python**. Then, you can run any of the following algorithms:
* Balanced Cut (clustering)
* Spectral Clustering (clustering)
* HITS (hubs vs. authorities analytics)
* Leiden (community detection)
* Katz Centrality
* Betweenness Centrality

All of the algorithms above are powered by **[Nvidia cuGraph](https://www.nvidia.com/en-us/)** and they will execute on **GPU**.

## Prerequisites 

To follow the tutorial, please install:
- [Docker](https://docs.docker.com/get-docker/) - needed to run `memgraph/memgraph-mage:1.3-cugraph-22.02-cuda-11.5` image we use in jupyter notebook
- [Jupyter](https://jupyter.org/install) - using jupyter-notebook you can write  `CSV` importing and graph analytics in one file
- [GQLAlchemy](https://pypi.org/project/gqlalchemy/) - used to connect Memgraph with Python
- [Memgraph Lab](https://memgraph.com/lab) - a GUI tool we use to visualize graphs

There are additional instructions in `cugraph_analytics.ipynb` notebook. Note, all `cuGraph` Docker images are available [here](https://hub.docker.com/r/memgraph/memgraph-mage/tags?page=1&name=cugraph). Depending on drivers on your machine, be sure to Download correct image. 
Check out compatibility between drivers and CUDA on official [NVIDIA page](https://docs.nvidia.com/deploy/cuda-compatibility/index.html).

## Data

The **CSV** files containing the [**Facebook** dataset](https://snap.stanford.edu/data/gemsec-Facebook.html) have the following structure:
```
node_1,node_2
0,1794
0,3102
0,16645
```
The dataset consists of verified Facebook pages belonging to different categories and dating back to November 2017. 
Nodes represent the pages, and relationships are mutual likes among them. The nodes are reindexed (starting from 0) to achieve a certain level of anonymity. 

## Run It
All the instructions are available in `cugraph_analytics.ipynb` notebook.
