<?php
require_once __DIR__ . '/vendor/autoload.php';

// Create a connection class and specify target host and port, default is localhost.
$conn = new \Bolt\connection\Socket();
// Create a new Bolt instance and provide connection object.
$bolt = new \Bolt\Bolt($conn);
// Set available Bolt versions for Memgraph.
$bolt->setProtocolVersions(4.1, 4, 3);
// Build and get protocol version instance which creates connection and executes handshake.
$protocol = $bolt->build();
// Login to database with credentials.
$protocol->hello(\Bolt\helpers\Auth::basic('username', 'password'));

$protocol
    ->run('MATCH (n) RETURN count(n) AS number_of_nodes',)
    ->pull();


// Server responses are waiting to be fetched through iterator.
$rows = iterator_to_array($protocol->getResponses(), false);
// Get content from requested record.
$row = $rows[1]->getContent();

echo '<br>';

// ----------------------------------------------

echo '<h1>Game of Graphs - Graph analytics on a GoT dataset</h1>';

echo '<h2>Test the connection to Memgraph</h2>';

echo "<p>Let's see if there's anything in the database. To do that, we can count the number of nodes in the database with the following Cypher query:<p>";

echo "<p><code>MATCH (n) RETURN count(n) AS number_of_nodes;</code><p>";

echo '<b>Count nodes: </b>' . $row[0];

// ----------------------------------------------

$protocol
    ->run('MATCH ()-[r]->() RETURN count(r) AS number_of_relationships',)
    ->pull();

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();

echo '<br>';
echo "<p>In the similar way, you can determine the number of relationships in the database:<p>";

echo "<p><code>MATCH ()-[r]->() RETURN count(r) AS number_of_relationships;</code><p>";
echo '<b>Count relationships: </b>' . $row[0] . '<br>';

// ----------------------------------------------

echo '<h2>Simple analytics</h2>';
echo "<p>Let's first list the total number of seasons in the dataset:<p>";
echo "<p><code>MATCH (s:Season) RETURN count(s) AS total_number_of_seasons;</code><p>";

$protocol
    ->run('MATCH (s:Season) RETURN count(s) AS total_number_of_seasons',)
    ->pull();

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();


echo '<b>Total number of seasons: </b>' . $row[0] . '<br>';

// ----------------------------------------------
echo "<p>Let's also see how many characters and deaths there are in the dataset:<p>";

$protocol
    ->run('MATCH (c:Character) RETURN count(c) AS total_number_of_characters',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();

echo "<p><code> MATCH (c:Character)
RETURN count(c) AS total_number_of_characters;</code><p>";
echo '<b>Total number of characters: </b>' . $row[0] . '<br>';
// ----------------------------------------------

$protocol
    ->run('MATCH (d:Death) RETURN count(d) AS total_number_of_deaths',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();

echo "<p><code>MATCH (d:Death)
RETURN count(d) AS total_number_of_deaths;</code><p>";
echo '<b>Total number of deaths: </b>' . $row[0] . '<br>';

// ----------------------------------------------


echo "<p>Next, we should check how many episodes are within each season in the dataset:<p>";
echo "<p><code>    MATCH (e:Episode)-[:PART_OF]->(s:Season) RETURN s, collect(e) AS episodes ORDER BY s.number;</code><p>";
$protocol
    ->run('MATCH (e:Episode)-[:PART_OF]->(s:Season) RETURN s, collect(e) AS episodes ORDER BY s.number',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);

// $response is instance of \Bolt\protocol\Response.
// First response is SUCCESS message for RUN message.
// Second response is RECORD message for PULL message.
// Third response is SUCCESS message for PULL message.
// start from 1, since RECORD starts there and finish at the end of RECORD message, before SUCCESS
for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo '<b>Season: </b>' . $row[0]->properties()['number'] . "<b> Number of episodes: </b>" . sizeof($row[1]);
    echo '<br>';
}


// ----------------------------------------------
echo '<h2>No deaths episodes</h2>';
echo "<p>We all know that Game of Thones is famous for the amount of (important) people that died throughout the TV show. <br>
But, there are couple of exceptions - episodes in which no one died! <br> 
Above we listed the number of episodes per season that are in the dataset, and that leads us to the conclusion on how many episodes have no deaths in them. <br>
After applying a bit of Google magic, we can find which episodes have no deaths in them. But, there we have <b>three episodes</b> in the dataset which are not noted as no death episodes. <br>
<b>These three episodes are: Valar Dohaeris (S03, E01), Dark Wings, Dark Words (S03, E02) and A Knight of the Seven Kingdoms (S08, E02)</b><br>
Here are the names of the characters who died in those episode in our dataset:<p>";
$protocol
    ->run('MATCH (c:Character)-[:VICTIM_IN]->(:Death)-[:HAPPENED_IN]->(:Episode {number: 1})-[:PART_OF]->(:Season {number: 3}) RETURN c',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();

echo 'Valar Dohaeris episode: <b>' . $row[0]->properties()['name'] . '</b>';
// ----------------------------------------------

$protocol
    ->run('MATCH (c:Character)-[v:VICTIM_IN]->(d:Death)-[:HAPPENED_IN]->(e:Episode {number: 2})-[p:PART_OF]->(s:Season {number: 3}) RETURN c',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();

echo '<br>';

echo 'Dark Wings, Dark Words episode:  <b>' . $row[0]->properties()['name'] . '</b>';
// ----------------------------------------------
$protocol
    ->run('MATCH (c:Character)-[v:VICTIM_IN]->(d:Death)-[:HAPPENED_IN]->(e:Episode {number: 2})-[p:PART_OF]->(s:Season {number: 8}) RETURN c',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();

echo '<br>';

echo 'A Knight of the Seven Kingdoms episode:  <b>' . $row[0]->properties()['name'] . '</b>';
// ----------------------------------------------

echo '<h2>Characters who killed themselves</h2>';

echo "<p>There were a couple of characters that managed to run away from the other killers, but still ended up dead. Let's see how:<p>";


$protocol
    ->run('MATCH (c:Character)-[k:KILLED]->(d:Character) WHERE c = d RETURN c, k',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Character: ' . $row[0]->properties()['name'] . " | Method: " . $row[1]->properties()['method'];
    echo '<br>';
}
// ----------------------------------------------
echo '<h2>Graph traversals and PageRank</h2>';

echo "<p>What part of Westeros should you avoid so you don't get killed?<p>";


$protocol
    ->run('MATCH p=(:Death)-[:HAPPENED_IN]->(:Location) WITH project(p) AS subgraph CALL pagerank.get(subgraph)
    YIELD node, rank
    WITH node, rank
    WHERE node:Location
    RETURN node, rank
    ORDER BY rank DESC
    LIMIT 10;',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);

for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Location: ' . $row[0]->properties()['name'] . " | Rank: " . $row[1];
    echo '<br>';
}

// ----------------------------------------------

echo "<p>Let's list the top 10 locations where the most deaths occurred:<p>";

$protocol
    ->run('MATCH (l:Location)<-[:HAPPENED_IN]-(d:Death) 
    RETURN l AS location, count(d) AS death_count 
    ORDER BY death_count 
    DESC LIMIT 10',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo $row[0]->properties()['name'] . " " . $row[1];
    echo '<br>';
}

// ----------------------------------------------

echo "<p>In which episode would you most probably die?<p>";

$protocol
    ->run('MATCH p=(:Death)-[:HAPPENED_IN]->(:Episode)
    WITH project(p) AS subgraph
    CALL pagerank.get(subgraph)
    YIELD node, rank
    WITH node, rank
    WHERE node:Episode
    RETURN node, rank
    ORDER BY rank DESC
    LIMIT 10;',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Episode: ' . $row[0]->properties()['name'] . " | Rank: " . $row[1];
    echo '<br>';
}

// ----------------------------------------------

echo "<p>Which episodes have the most deaths?<p>";

$protocol
    ->run('MATCH (d:Death)-[:HAPPENED_IN]->(e:Episode) 
    RETURN e AS episode, count(d) AS death_count 
    ORDER BY death_count DESC 
    LIMIT 10',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo $row[0]->properties()['name'] . " " . $row[1];
    echo '<br>';
}

// ----------------------------------------------
echo '<h1>Seasons and allegiances</h1>';
echo '<h2>Number of kills per season</h2>';

$protocol
    ->run('MATCH (d:Death)-[:HAPPENED_IN]->(s:Season) 
    RETURN s AS season, count(d) AS death_count 
    ORDER BY death_count DESC',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo $row[0]->properties()['number'] . " " . $row[1];
    echo '<br>';
}
// ----------------------------------------------

echo '<h2>Top seasons by IMDB rating</h2>';

$protocol
    ->run('MATCH (e:Episode)-[:PART_OF]->(s:Season) 
    RETURN s AS season, avg(e.imdb_rating) AS rating 
    ORDER BY rating DESC',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Season: ' . $row[0]->properties()['number'] . " | Rating: " . $row[1];
    echo '<br>';
}

// ----------------------------------------------

echo '<h2>Top 10 allegiances by the kill/death ratio (KDR)</h2>';


$protocol
    ->run('MATCH (:Character)-[death:KILLED]->(:Character)-[:LOYAL_TO]->(a:Allegiance) 
    WITH a, sum(death.count) AS deaths 
    MATCH (:Character)<-[kill:KILLED]-(:Character)-[:LOYAL_TO]->(a) 
    RETURN a AS allegiance, sum(kill.count) AS kills, deaths, round(100 *(tofloat(sum(kill.count))/deaths))/100 AS KDR 
    ORDER BY KDR DESC 
    LIMIT 10',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);

for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Allegiance: ' . $row[0]->properties()['name'] . " | Kills: " . $row[1] . " | Deaths: " . $row[2] . " | KDR: " . $row[3];
    echo '<br>';
}

// ----------------------------------------------
echo '<h2>Battle of Bastards causalties - Starks vs Boltons</h2>';

$protocol
    ->run('MATCH (c:Character)-[:LOYAL_TO]->(a:Allegiance) 
    MATCH (c)-[:VICTIM_IN]-(d:Death)-[:HAPPENED_IN]-(:Episode {name: "Battle of the Bastards"}) 
    RETURN a AS house, count(d) AS death_count 
    ORDER BY death_count DESC 
    LIMIT 2',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Allegiance: ' . $row[0]->properties()['name'] . " | Deaths: " . $row[1];
    echo '<br>';
}

// ----------------------------------------------

echo '<h1>Is Daenerys that bad?</h1>';

$protocol
    ->run('MATCH (daenerys:Character {name: "Daenerys Targaryen"})-[:KILLED]->(victim:Character) 
    MATCH (daenerys)-[:KILLER_IN]->(d:Death)<-[:VICTIM_IN]-(victim) 
    MATCH (d)-[:HAPPENED_IN]-(e:Episode) 
    RETURN DISTINCT victim, count(d) AS kill_count, e AS episode 
    ORDER BY kill_count DESC;',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Victim: ' . $row[0]->properties()['name'] . " | Kill Count: " . $row[1] . " | Episode name: " . $row[2]->properties()['name'];
    echo '<br>';
}

// ----------------------------------------------

echo '<h2>Who is the killer influencer?</h2>';

$protocol
    ->run('CALL betweenness_centrality.get(False) YIELD node, betweenness_centrality 
    WITH node, betweenness_centrality 
    WHERE "Character" IN labels(node) 
    RETURN node, betweenness_centrality 
    ORDER BY betweenness_centrality DESC LIMIT 10',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo $row[0]->properties()['name'] . " | BC: " . $row[1];
    echo '<br>';
}

// ----------------------------------------------

echo '<h2>Who would have survived if Jon stayed dead?</h2>';
$protocol
    ->run('MATCH (jon:Character {name: "Jon Snow"})-[:KILLED]->(victim:Character)
    MATCH (jon)-[:VICTIM_IN]->(jon_death:Death)
    MATCH (jon)-[:KILLER_IN]->(victim_death:Death)<-[:VICTIM_IN]-(victim)
    WHERE victim_death.order > jon_death.order
    RETURN DISTINCT victim, count(victim_death) AS kill_count
    ORDER BY kill_count DESC;',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Victim: ' . $row[0]->properties()['name'] . " | Kill Count: " . $row[1];
    echo '<br>';
}

// ----------------------------------------------
echo '<h2>Who killed Jon Snow?</h2>';

$protocol
    ->run('MATCH (jon:Character {name: "Jon Snow"})-[v:VICTIM_IN]->(d:Death)<-[k:KILLER_IN]-(c:Character) 
    WITH jon, v, d, k, c 
    MATCH (c)-[:VICTIM_IN]->(:Death)<-[:KILLER_IN]-(:Character) 
    RETURN c, jon;',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);
$row = $rows[1]->getContent();
echo $row[0]->properties()['name'] . " killed " . $row[1]->properties()["name"] . " and " . $row[1]->properties()["name"] . " killed " . $row[0]->properties()["name"];

// ----------------------------------------------
echo '<h2>Who is the biggest traitor?</h2>';

$protocol
    ->run('MATCH (killer:Character)-[:KILLED]->(victim:Character) 
    MATCH (killer)-[:LOYAL_TO]->(a:Allegiance)<-[:LOYAL_TO]-(victim) 
    RETURN killer AS traitor, count(victim) AS kill_count, a AS allegiance 
    ORDER BY kill_count DESC 
    LIMIT 10',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);


for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    echo 'Traitor: ' . $row[0]->properties()['name'] . " | Kill Count: " . $row[1] . " | Allegiance: " . $row[2]->properties()['name'];
    echo '<br>';
}
// ----------------------------------------------

echo '<h1>Dijkstra killing it</h1>';
echo "<p>Memgraph supports graph algorithms as well. Let's use Dijkstra's shortest path algorithm to show the shortest path of killings with highest kill count. <br>
An example kill path is: Jon Snow killed 5 Lannister Soldiers and they killed 10 Stark soldiers with total kill_count of 15.<p>";

$protocol
    ->run('MATCH p = (:Character)-[:KILLED * wShortest (e,v | e.count) kill_count]->(:Character) 
    RETURN nodes(p) AS kill_list, relationships(p) AS connections, kill_count 
    ORDER BY kill_count DESC 
    LIMIT 1',)
    ->pull();   

$rows = iterator_to_array($protocol->getResponses(), false);

for ($x = 1; $x < sizeof($rows) - 1; $x++) {
    $row = $rows[$x]->getContent();
    foreach ($row[0] as $kill) {
        echo $kill->properties()['name'] . "<br>";
    }
}
?>
