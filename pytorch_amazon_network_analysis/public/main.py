import argparse
import json

import numpy as np
import nltk.sentiment as nltk_sentiment
import nltk.corpus as nltk_corpus
import os
import string
import spacy
from typing import List, Dict, Any

dir_path = os.path.dirname(os.path.realpath(__file__))

sia = nltk_sentiment.SentimentIntensityAnalyzer()
stop_words = set(nltk_corpus.stopwords.words('english'))
nlp = spacy.load("en_core_web_sm")

class ProductReview:
    def __init__(self, product_id: str, title: str, user_id: str, review_score: float, review_time: int,
                 review_text: str, analysis_vector: List[float], profile_name: str):
        profile_name = profile_name.replace("'", "")
        profile_name = profile_name.replace('"', "")
        review_text = review_text.replace("'", "")
        review_text = review_text.replace('"', "")
        self.product_id = product_id
        self.title = title
        self.user_id = user_id
        self.review_time = review_time
        self.review_score = review_score
        self.review_text = review_text
        self.feature_edge = analysis_vector
        self.profile_name = profile_name

    def __str__(self):
        return f"product_id:{self.product_id} user_id:{self.user_id}, title:{self.title}, time: {self.review_time}"

    def make_queries(self):
        return f"MERGE (a:User {{id: '{self.user_id}', profile_name:'{self.profile_name}'}}) " \
               f"MERGE (b:Item {{id: '{self.product_id}'}}) " \
               f"MERGE (a)-[:REVIEWED {{review_text:'{self.review_text}', feature: {self.feature_edge}, " \
               f"review_time:{self.review_time}, review_score:{self.review_score}}}]->(b);"


def nlp_analysis(sentence: str):
    char_num = len(sentence)
    char_num_wo_space = len(sentence) - sentence.count(' ')
    char_num_alphabetical = len([c for c in sentence if c.isalpha()])
    char_num_digits = len([c for c in sentence if c.isdigit()])
    char_num_uppercase = len([c for c in sentence if c.isupper()])
    char_num_whitespace = len([c for c in sentence if c.isspace()])
    char_num_special = len([c for c in sentence if c in string.punctuation])

    doc = nlp(sentence)

    num_words = len(doc)
    num_unique_words = len(set(doc))
    num_long_words = len([w for w in doc if len(w) > 6])
    average_word_length = sum(len(w) for w in doc) / len(doc)

    stop_words_sentence = [w for w in doc if w.lower in stop_words]
    num_unique_stopwords = len(set(stop_words_sentence))
    fraction_stop_words = len(stop_words_sentence) / len(doc)

    scores = sia.polarity_scores(sentence)

    num_of_sentences = len(list(doc.sents))
    num_long_sentences = len([sent for sent in doc.sents if len(sent) > 10])
    num_words_sentence = [len(sent) for sent in doc.sents]

    analysis_vector = [char_num,
                       char_num_wo_space,
                       char_num_alphabetical * 1.0 / char_num,
                       char_num_digits * 1.0 / char_num,
                       char_num_uppercase * 1.0 / char_num,
                       char_num_whitespace * 1.0 / char_num,
                       char_num_special * 1.0 / char_num,
                       num_words,
                       num_unique_words,
                       num_long_words,
                       average_word_length,
                       num_unique_stopwords,
                       fraction_stop_words,
                       num_of_sentences,
                       num_long_sentences,
                       sum(num_words_sentence) * 1.0 / num_of_sentences]

    for key, value in scores.items():
        analysis_vector.append(value)

    return np.array(analysis_vector, dtype=float)


def read_dataset(dataset_path: str):
    with open(dataset_path) as json_file:
        product_reviews_json = json.load(json_file)

    all_product_reviews = []
    for review in product_reviews_json:

        if not all(name in review for name in ["product/productId", "product/title", "review/userId",
                                               "review/score", "review/time", "review/text", "review/profileName"]):
            continue

        analysis_vector = nlp_analysis(review["review/text"])

        all_product_reviews.append(ProductReview(product_id=review["product/productId"],
                                                 title=review["product/title"],
                                                 user_id=review["review/userId"],
                                                 review_score=float(review["review/score"]),
                                                 review_time=int(review["review/time"]),
                                                 review_text=review["review/text"],
                                                 analysis_vector=[float(e) for e in analysis_vector],
                                                 profile_name=review["review/profileName"]))
        print(len(all_product_reviews))
    sorted(all_product_reviews, key=lambda x: x.review_time)
    all_product_reviews = list(filter(lambda x: x.review_score == 5.0, all_product_reviews))
    return all_product_reviews


def write_features(all_product_reviews: List[ProductReview]):
    # one row of graph data contains source, destination, review_time and edge_index
    graph_data_len = 4
    graph_data = np.empty((0, 4), dtype=int)
    all_edge_features = np.empty((0, 20), dtype=float)

    all_user_ids = set([show_review.user_id for show_review in all_product_reviews])
    all_user_ids_mappings = {user_id: i for i, user_id in enumerate(all_user_ids)}

    max_num_user_id = len(all_user_ids_mappings)

    all_target_ids = set([show_review.product_id for show_review in all_product_reviews])
    all_target_ids_mappings = {product_id: (max_num_user_id + i) for i, product_id in enumerate(all_target_ids)}

    for i, product_review in enumerate(all_product_reviews):
        graph_info = np.array(
            [all_user_ids_mappings[product_review.user_id], all_target_ids_mappings[product_review.product_id],
             product_review.review_time, i], dtype=int)
        edge_features = np.array(product_review.feature_edge, dtype=float)

        graph_data = np.append(graph_data, graph_info)
        all_edge_features = np.append(all_edge_features, edge_features)

    graph_data_path = f'{dir_path}/graph_data.npy'
    edge_features_path = f'{dir_path}/edge_features.npy'

    # saving data to files
    np.save(graph_data_path, graph_data)
    np.save(edge_features_path, all_edge_features)


def make_queries(all_product_reviews: List[ProductReview]):
    queries = [product.make_queries() for product in all_product_reviews]

    final_queries = ["MATCH (n) DETACH DELETE n;"]
    final_queries.extend(queries)

    with open('queries.cypherl', "w") as fh:
        fh.write("\n".join(final_queries))


def get_args() -> Dict[str, Any]:
    parser = argparse.ArgumentParser()
    parser.add_argument("--save", required=True, choices=['queries', 'npy', 'all'],
                        help="save features with queries, numpy or both")
    args = parser.parse_args()
    config = dict()
    for arg in vars(args):
        config[arg] = getattr(args, arg)
    return config


def main():
    args = get_args()

    all_product_reviews = read_dataset(dataset_path=f"{dir_path}/../data/product_reviews.json")

    if args["save"] == "queries":
        make_queries(all_product_reviews)
    elif args["save"] == "npy":
        write_features(all_product_reviews)
    else:
        make_queries(all_product_reviews)
        write_features(all_product_reviews)


if __name__ == "__main__":
    main()

# 'CALL tgn.set_params("self_supervised", 200, 2, "graph_attn", 100, 100, 20, 20, 100, 10, "identity", "last", "gru", 1) YIELD *;'
#  'CALL tgn.set_mode("eval") YIELD *;'
