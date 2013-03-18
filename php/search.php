<?php

/**
 * search.php
 *
 * Searches database for landmarks by keyword for name, description, or type
 * (takes
 *
 *.---.      .                    .
 *  |  o     |                    |
 *  |  .  .-.| .-. .,-.  .-.  .-. | .--.
 *  |  | (   |(.-' |   )(   )(   )| `--.
 *  '-' `-`-'`-`--'|`-'  `-'  `-' `-`--' v0.2

 *  Copyright (C) 2012-2013 Open Technology Institute <tidepools@opentechinstitute.org>
 *      Lead: Jonathan Baldwin
 *      Contributors: Lisa J. Lovchik
 *      This file is part of Tidepools <http://www.tidepools.co>

 *  Tidepools is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.

 *  Tidepools is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.

 *  You should have received a copy of the GNU General Public License
 *  along with Tidepools.  If not, see <http://www.gnu.org/licenses/>.
 */


require('tidepools_variables.php');

$counter = 1;

$maps = (isset($_POST['mapIDs']) ? $_POST['mapIDs'] : null);

//temporary to create array from string on future map layer iteration
$maps = explode( ',', $maps );

$searchResult = (isset($_POST['data']) ? $_POST['data'] : null);
$searchResult = stripslashesDeep($searchResult);

$search1 = array();
$search2 = array();
$final = array();



try {

    // open connection to MongoDB server
    $m = new Mongo('localhost');

    // access database
    $db = $m -> selectDB($DBname);


    //------ MONGO DB ESCAPE STRING -------//
    /*
        $pattern = '$';
        $replacement = '\$';
        echo preg_replace($pattern, $replacement, $description);
        */
    //------------------------------------//

    // get 'landmarks' collection
    $type = 'landmarks';
    $collection = $db -> $type;

    // sanitize text box input
    $sanTerm = substr($_POST['searchTerm'], 0, 40);
    $sanTerm = strip_tags($sanTerm);
    $sanTermLowercase = strtolower($sanTerm);


    // landmark type search, if applicable
    $landmarkTypeKey = array_search($sanTermLowercase, $landmarkTypes);

    if (array_search($sanTermLowercase, $landmarkTypes) !== FALSE) {
        $landmarkType = $landmarkTypes[$landmarkTypeKey];
    }

    if (isset($landmarkType)) {
        $query = array(
            'type' => $landmarkType
        );

        $cursor = $collection -> find($query);
        $cursor = iterator_to_array($cursor);
        array_push($final, $cursor);
    }

    // keyword search
    $searchKey = array('name', 'description');

    foreach ($searchKey as $v) {
        $query = array(
            "$v" => new MongoRegex(
                '/' . $sanTerm . '/i'
            )
        );

        $cursor = $collection -> find($query);
        $cursor = iterator_to_array($cursor);
        array_push($final, $cursor);
    }

    unset($v);

/*  TEMPORARY DISABLING GEOLOCATION SEARCH - THIS WILL HAVE ITS OWN BUTTON LATER

    $key = $m -> $_POST['searchKey'];

    // prepare to query DB
    switch ($key) {
        case "name":
        case "description":
        case "type":
            // set up name, description, and type searches by keyword
            $query = array(
                "$key" => new MongoRegex(
                    '/' . $sanTerm . '/i'
                )
            );
            // echo '<b>Locations with "' . $sanTerm . '" in their '
            //    . $key . '</span></b><br /><br />';

            break;
        case "loc":
            // get starting point and radius
            // starting point is hard-coded into index.html for now
            // later, this will be handled by the map interface
            $sanTerm = (float) $sanTerm;
            $distanceUnits = $_POST['distUnits'];

            if ($distanceUnits == 'mi') {
                // ~ 69 mi per 1 degree latitude or longitude
                $maxDistance = (float) $sanTerm / 69;
            }
            else {
                $distanceUnits = "km";
                // ~111 km per 1 degree latitude or longitude
                $maxDistance = (float) $sanTerm / 111;
            }

            $lon = (float) $_POST['lon'];
            $lat = (float) $_POST['lat'];
            $lonlat = array($lon, $lat);

            // echo '<b>Locations within ' . $sanTerm . ' '.
            //    $distanceUnits . ' of ' . $lon .  ' longitude, ' .
            //    $lat . ' latitude</b><br />';


            // set up location search as geospatial indexing search
            $query = array(
                "$key" => array(
                    '$near' => $lonlat,
                    '$maxDistance' => $maxDistance
                    )
                );
            break;
        case "time":
            // to be added in the future
            break;
        default:
            echo "<h3>Error - invalid search type</h3>";
    }


    // query database

    $cursor = $collection -> find($query);

    $cursor = iterator_to_array($cursor);

    array_push($final, $cursor);
*/


    //var_dump($cursor);

    // display search results
    // echo '<b>' . $cursor -> count() . ' item(s) found.</b><br /><br />';

    // convert search results to JSON format
    //$final = array();

    //$final = iterator_to_array($cursor);



   // $final = array_push($final, $cursor);



    // echo '<br /><br />';


    /*
    // print raw data for testing purposes
    echo '<h3>First, the raw data:</h3>';

    while ($cursor -> hasNext()) {
        var_dump($cursor -> getNext());
        echo "<br /><br />";
    }
    echo '<h3>Now, a little neater:</h3>';
    */

/* TAKE THIS DISPLAY OUT FOR NOW

    // iterate through the result set and print each document
    foreach ($cursor as $v) {
        echo 'Name: ' . $v['name'] . '<br />';
        echo 'Time: ' . $v['stats']['time']['start'] . ' - '
            . $v['stats']['time']['end'] . '<br />';
        echo 'Location: ' . $v['loc'][0] . ', ' . $v['loc'][1] . '<br />';
        echo 'Description: ' . $v['description'] . '<br />';
        echo 'Type: ' . $v['type'] . '<br />';
        echo '<br />';
    }

    unset ($v); // remove lingering foreach() values from memory


*/

    $final = json_encode($final);
    print_r($final);

    // disconnect from server
    $m -> close();
} catch (MongoConnectionException $e) {
    die('Error connecting to MongoDB server - is the "mongo" process running?');
} catch (MongoException $e) {
    die('Error: ' . $e -> getMessage());
}

function stripslashesDeep($value)
{
    $value = is_array($value)
        ? array_map('stripslashesDeep', $value)
        : stripslashes($value);

    return $value;
}
