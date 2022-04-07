#PyTorch x Amazon product reviews = :heart:

This  **Jupyter Notebook** is aimed to make it easy to start **exploring and learning**
about **Temporal Graph Networks** and **GNNs** as part of **Memgraph** and in general.


## Table of contents
 * [Introduction to repo](#introduction)
 * [What are Graph Neural Networks and TGN](#what-are-gnns)
 * [Amazon product data visualized](#amazon-product-data-visualized)

##Introduction

This directory represents example of how to use **[Temporal graph networks]()** to train and evaluate **mean average precision**
on **[Amazon product graph]()** dataset.


If you want to read more about **GQLAlchemy**, go to the following **[link](https://memgraph.com/docs/gqlalchemy/)**.
In a nutshell, **GQLAlchemy** is a fully open-source Python library that aims to be the go-to **Object Graph Mapper (OGM)** -- 
a link between Graph Database objects and Python objects.


## What are GNNs


## Amazon product data visualized
Insert an from Memgraph Lab


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
