"""
 The purpose of this file is to create the product_reviews.json file, which we use to create Cypher queries.
 If you want to use some test some other cypher queries, different from ones we prepared, go to the
 http://snap.stanford.edu/data/amazon/, download, unzip and save your dataset in `data` folder. Afterwards,
 you can process it with json_converter.py

"""
import os
import re
import json

dir_path = os.path.dirname(os.path.realpath(__file__))


def convert_reviews_json(product_reviews_path: str, num_items: int) -> str:
    with open(product_reviews_path, "r") as fh:
        lines = fh.readlines()
    grouped_objects = []
    curr_obj = []
    for line in lines:
        if len(line.strip()) == 0:
            grouped_objects.append(curr_obj)
            curr_obj = []
            continue
        curr_obj.append(line.strip())

    products_list = []
    for i, obj in enumerate(grouped_objects):
        if i > num_items:
            break
        product = {}

        for line in obj:
            matches = re.match(r'(.*)(:)(.*)', line)
            groups = matches.groups()
            product[groups[0]] = groups[2]
        products_list.append(product)

    return json.dumps(products_list)


def write_to_file(json_string: str, file_name: str) -> None:
    with open(file_name, "w") as write_file:
        write_file.write(json_string)


def main():
    products_path = f"{dir_path}/../data/product_reviews.txt"
    print(f"[INFO] Converting objects from {products_path} to JSON string")
    json_string = convert_reviews_json(f"{dir_path}/../data/product_reviews.txt", num_items=1500)
    json_file_path = f"{dir_path}/../data/product_reviews.json"
    print(f"[INFO] Writing JSON to file {json_file_path}")
    write_to_file(json_string, json_file_path)
    print(f"[INFO] Writing to file done! Now run 'python3 public/main.py' to create queries.")


if __name__ == "__main__":
    main()
