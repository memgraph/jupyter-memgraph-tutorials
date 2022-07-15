import mgp
from typing import Any, List
import enum
import numpy as np
import igraph
from scipy.stats import spearmanr

MIN_TRADING_DAYS = 2
EDGE_ATTR = "weight"


class CorrelationMeasures(enum.Enum):
    PEARSON = "pearson"
    SPEARMANR = "spearmanr"


class InvalidNumberOfTradingDaysException(Exception):
    pass


class InvalidCorrelationMeasureException(Exception):
    pass


class NotEnoughOfDataException(Exception):
    pass


class InvalidNumberOfBestPerformingStocksException(Exception):
    pass


@mgp.read_proc
def get(
    context: mgp.ProcCtx,
    stock_node_ticker: List[str],
    stock_trading_values: List[Any],
    n_trading_days_back: int = 3,
    n_best_performing: int = 5,
    resolution_parameter: float = 0.6,
    correlation_measure: str = "pearson",
    number_of_iterations: int = -1,
) -> mgp.Record(community_index=int, community=str):
    """Procedure for constructing portfolio and getting communities detected by leiden algorithm.

    Args:
        stocks: stock node tickers
        values: values used for calculating correlations
        n_trading_days_back: number of days taking in consideration while calculating correlations
        n_best_performing: number of best performing stocks to pick from each community
        resolution_parameter: the resolution parameter to use. Higher resolutions lead to more smaller communities, while lower resolutions lead to fewer larger communities.
        correlation_measure: measure to use for calculating correlations between stocks
        number_of_iterations: number of iterations used in leiden algorithm

    Returns:
        Community indexes and communities

    The procedure can be invoked in openCypher using the following call:

    MATCH (s:Stock)-[r:Traded_On]->(d:TradingDay)
    WHERE d.date < "2022-04-27"
    WITH collect(s.ticker) as stocks,collect(r.close - r.open) as daily_returns
    CALL construct_portfolio.get(stocks,daily_returns,5,5,0.7)
    YIELD community_index, community
    RETURN community_index, community;

    """

    if n_trading_days_back <= MIN_TRADING_DAYS:
        raise InvalidNumberOfTradingDaysException(
            "Number of last trading days must be greater than 2."
        )

    if correlation_measure not in [CorrelationMeasures.PEARSON.value, CorrelationMeasures.SPEARMANR.value]:
        raise InvalidCorrelationMeasureException(
            "Correlation measure can only be either pearson or spearmanr"
        )

    stock_tickers = np.sort(np.unique(stock_node_ticker))

    if len(stock_trading_values) == 0 or len(stock_trading_values) < len(stock_tickers) * 3:
        raise NotEnoughOfDataException(
            f"There need to be at least three entries of data for each stock"
        )

    if n_best_performing < 1:
        raise InvalidNumberOfBestPerformingStocksException(
            "Number of best perfoming stocks to take from each community needs to be bigger than 0"
        )

    stock_node_ticker = _split_data(stock_node_ticker, len(
        stock_tickers), n_trading_days_back)

    sorted_indices = _get_sorted_indices(stock_node_ticker)

    stock_trading_values = _split_data_and_sort(
        stock_trading_values, n_trading_days_back, len(
            stock_tickers), sorted_indices
    )

    correlations = _calculate_correlations(
        stock_trading_values, correlation_measure
    )

    graph = _create_igraph_from_matrix(correlations, EDGE_ATTR)

    communities = graph.community_leiden(
        weights=graph.es[EDGE_ATTR],
        resolution_parameter=resolution_parameter,
        n_iterations=number_of_iterations,
    )

    return get_records(
        communities, stock_tickers, stock_trading_values, n_best_performing
    )


def get_records(
    communities: List[List[int]],
    stock_tickers: List[str],
    stock_trading_values: List[float],
    n_best_performing: int,
):
    """Function for creating returning mgp.Record data

    Args:
        communities (List[List[int]]): List of communities with belonging members indexes
        stock_tickers (List[str]): stock tickers
        stock_trading_values (List[float]): stock trading values
        n_best_performing (int): number of best performing stocks to pick from each community

    Returns:
        List[mgp.Record(community_index = int, community = str)]: list of mgp.Record(community_index = int, community = str)

    """

    records = []
    for i, members in enumerate(communities):
        members = _get_n_best_performing(
            stock_trading_values, members, n_best_performing
        )

        stocks_in_community = ", ".join(stock_tickers[members])
        records.append(mgp.Record(community_index=i,
                       community=stocks_in_community))

    return records


def _split_data(data: List, number_of_stocks: int, number_of_elements_in_each_bin: int) -> List[List[float]]:
    """Function used for splitting data.

    Args:
        data (List): data
        number_of_elements_in_each_bin (int): number of elements that will be in each bin after splitting

    Returns:
        List[List[float]]: splitted data

    """
    data = data[-number_of_stocks * number_of_elements_in_each_bin:]

    return np.array(np.array_split(data, number_of_elements_in_each_bin))


def _create_igraph_from_matrix(matrix: List[List[float]], edge_attr: str):
    """Create igraph graph from weighted 2D matrix
    Args:
        matrix (List[List[float]]): weighted matrix
    Returns:
        Igraph graph
    """
    graph = igraph.Graph.Weighted_Adjacency(
        matrix, mode=igraph.ADJ_UNDIRECTED, attr=edge_attr, loops=False
    )

    return graph


def _get_sorted_indices(stock_tickers: List[str]) -> List[List[int]]:
    """Returns sorted indices

    Args:
        stock_tickers (List[str]): stock tickers on each day

    Returns:
       List[List[int]]: sorted indices on each day

    """

    sorted_indices = []
    for i in range(stock_tickers.shape[0]):
        sorted_indices.append(np.argsort(stock_tickers[i]).tolist())

    return sorted_indices


def _split_data_and_sort(
    data: List, number_of_days: int, number_of_stocks: int, sorted_indices: List[List[int]]
) -> List[List]:
    """Function used for splitting and sorting data.

    Args:
        data (List): data
        number_of_days (int): number of days for splitting
        sorted_indices (List[List[int]]): indices used for sorting in each day

    Returns:
        List: splitted and sorted 2D-array

    """
    data = data[-number_of_stocks * number_of_days:]
    x = _split_data(data, number_of_stocks, number_of_days)
    for i in range(number_of_days):
        x[i] = x[i][sorted_indices[i]]

    return x.transpose()


def _calculate_correlations(
    values: List[List[float]], measure: str = "pearson"
) -> List[List[float]]:
    """Calculate correlations between values

    Args:
        values (List[List[float]]): values
        measure (str, optional): measure. Defaults to 'pearson'.

    Returns:
        List[List[float]]: correlations

    """

    if measure == "pearson":
        return abs(np.corrcoef(values))
    elif measure == "spearmanr":
        return abs(spearmanr(values, axis=1))


def _get_n_best_performing(
    stock_trading_values: List[List[float]], members: List[int], n_best_performing: int
) -> List[int]:
    """From each community pick number of best performing

    Args:
        stock_trading_values (List[List[float]]): values of each stock
        members (List[int]): indices which are in community
        n_best_performing (int): number of best performing stocks

    Returns:
        List[int]: indices of best performing stocks in community

    """

    members = np.array(members)
    current_community_stock_trading_values = stock_trading_values[members]
    mean_current_community_stock_trading_values = np.mean(
        current_community_stock_trading_values, axis=1
    )
    sorted_indices = np.argsort(mean_current_community_stock_trading_values)

    return members[sorted_indices[-n_best_performing:]]
