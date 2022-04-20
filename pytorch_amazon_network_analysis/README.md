#PyTorch x Amazon product reviews = :heart:

Purpose of this  **Jupyter Notebook** is to make it easy to start **exploring and learning**
about **Temporal Graph Networks** and **GNNs** as part of **Memgraph** and in general.


## Table of contents
 * [Introduction to repo](#introduction)
 * [What are Graph Neural Networks and TGN](#what-are-gnns)
 * [Amazon product data visualized](#amazon-product-data-visualized)

##Introduction
This directory is an example of how to use **[Temporal Graph Networks](https://memgraph.com/docs/mage/query-modules/python/tgn)** for the
link prediction on **[Amazon product graph](http://snap.stanford.edu/data/amazon/productGraph/)** dataset.

In this Jupyter Notebook  we run **[Memgraph](https://memgraph.com/docs/memgraph/)** inside 
**[Docker](https://www.docker.com/)** and use **[GQLAlchemy](https://memgraph.com/docs/gqlalchemy/)** as a link between 
Graph Database objects and Python objects.


## What are GNNs
**Graph neural networks** present a neural network models dealing with convolutions over graphs. It all started
with **[Graph convolutional networks](https://arxiv.org/abs/1609.02907)** and idea of researchers to start exploring signals on graphs.
Actually those ideas started way before with [spectral clustering](https://arxiv.org/pdf/0711.0189.pdf)), 
but after several years of research and polishing signal exploration over graph, **graph neural networks** were born.
Afterwards, **GNNs** expanded and started adapting **inductive** learning methods, which helped with development of
**[temporal graph neural networks](https://towardsdatascience.com/temporal-graph-networks-ab8f327f2efe)**. 


## Amazon product data visualized
Here is an image from **Memgraph Lab** how our dataset **amazon product** looks:
![Dataset](images/amazon-user-item-dataset.png)


## About Amazon product graph dataset
All the information about dataset, you can find on the following **[link](http://snap.stanford.edu/data/amazon/productGraph/)**.

From the dataset description: 
```plaintext
This dataset contains product reviews and metadata from Amazon, 
including 143.7 million reviews spanning May 1996 - July 2014.

This dataset includes reviews (ratings, text, helpfulness votes), 
product metadata (descriptions, category information, price, brand, and image features), 
and links (also viewed/also bought graphs).
```

## Setup
1. Firstly, go to **[Stanford page](http://snap.stanford.edu/data/amazon/)** which contains Amazon product reviews.
2. Pick one of the datasets, download it, extract it and save it inside folder `pytorch_amazon_network_analysis/data/product_reviews.txt`
3. Use our script `./public/main.py` to create cypher queries. Those cypher queries we will later use in Jupyter Notebook
to send them to **Memgraph** using **GQLAlchemy**. 
4. Next go to the **Jupyter Notebook** in `./amazon_network_analysis.ipynb` and start predicting edges in Amazon dataset.


## Learning materials
If you wish to start exploring about **TGN** you can read one of the following:
* go to our [page](https://memgraph.com/docs/mage/query-modules/python/tgn) 
* read a paper about **[TGN](https://arxiv.org/pdf/2006.10637.pdf)**
* watch a video explanation about **[TGN](https://www.youtube.com/watch?v=0tw66aTfWaI&t=1s)**
