<?php

session_start();

// Declare the interface 'CRUD'
interface CRUD {
	
    /**
     * @param string $start
     * @param string $end
     * @return array
     */
    public function getBetweenDates(string $start, string $end);

    /**
     * @param array $attributes
     * @return array
     */
    public function create(array $attributes);

    /**
     * @param string $id
     * @param array $attributes
     * @return mixed
     */
    public function update(string $id, array $attributes);
}


// Implement the interface
class test implements CRUD {
	
	// Function that logs in to saleforce and get token for use in next function calls
	function getAccess()
    {
        $curl = curl_init();
        curl_setopt_array(
            $curl,
            array(
                CURLOPT_RETURNTRANSFER => TRUE,
                CURLOPT_URL            => "https://USER INSTANCE.my.salesforce.com" . "/services/oauth2/token",
                CURLOPT_POST           => TRUE,
                CURLOPT_POSTFIELDS     => http_build_query(
                    array(
                        'grant_type'    => "password",
                        'client_id'     => "consumer key",
                        'client_secret' => "consumer secret",
                        'username'      => "username",
                        'password'      => "user password" . "user token"
                    )
                )
            )
        );

        $response = json_decode(curl_exec($curl));
        curl_close($curl);

        $access_token = (isset($response->access_token) && $response->access_token != "") ? $response->access_token : die("Error - access token missing from response!");
        $instance_url = (isset($response->instance_url) && $response->instance_url != "") ? $response->instance_url : die("Error - instance URL missing from response!");

        return array(
            "accessToken" => $access_token,
            "instanceUrl" => $instance_url
        );
    }
	
	
	// Function to show what accounts are in the Accounts sObject currently
	function show_accounts($instance_url, $access_token) {

	    $query = "SELECT Name, Id, CreatedDate from Account LIMIT 100";
	    $url = "$instance_url/services/data/v20.0/query?q=" . urlencode($query);

	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

	    $json_response = curl_exec($curl);
	    curl_close($curl);
	    $response = json_decode($json_response, true);
	    $total_size = $response['totalSize'];

	    echo "$total_size record(s) returned<br/><br/>";
	    foreach ((array) $response['records'] as $record) {
	        echo $record['Id'] . ", " . $record['Name'] . $record['CreatedDate'] . "<br/>";
	    }
	    echo "<br/>";

	}

	
	// Get an array of string dates between 2 dates
	function getBetweenDates($start, $end) {
				
		$startD = $start[0];
		$instance_url = $start[1]; 
		$access_token = $start[2];
	
	    $query = "SELECT Name, Id , CreatedDate FROM Account WHERE CreatedDate >= $startD AND CreatedDate <= $end Limit 100";
	    $url = "$instance_url/services/data/v20.0/query?q=" . urlencode($query);

	    $curl = curl_init($url);
	    curl_setopt($curl, CURLOPT_HEADER, false);
	    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

	    $json_response = curl_exec($curl);
	    curl_close($curl);
	    $response = json_decode($json_response, true);
		//print_r($response); for code testing to see errors in query

		$total_size = $response['totalSize'];
	    echo "$total_size record(s) returned<br/><br/>";
	    foreach ((array) $response['records'] as $record) {
	        echo $record['Id'] . ", " . $record['Name'] . $record['CreatedDate'] . "<br/>";
	    }
	    echo "<br/>";
		
		
		$array = $response; 
		return $array;
	}
		
	// Function to create a new account
	public function create($attributes) {
		
	$name = $attributes[0];
	$instance_url = $attributes[1]; 
	$access_token = $attributes[2];
	
	$url = "$instance_url/services/data/v20.0/sobjects/Account/";
    $content = json_encode(array("Name" => $name));

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token", "Content-type: application/json"));
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $content);

    $json_response = curl_exec($curl);
    $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	if ( $status != 201 ) {
        die("Error: call to URL $url failed with status $status, response $json_response, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
    }
    echo "HTTP status $status creating account<br/><br/>";
	
    curl_close($curl);
    $response = json_decode($json_response, true);
    $id = $response["id"];
    echo "New record id $id<br/><br/>";
    return $id;
	}

	// Function to update the already existing account
	function update($id, $attributes) {
		
		$phone = $attributes[0];
		$type = $attributes[1];
		$instance_url = $attributes[2]; 
		$access_token = $attributes[3];
	
		$url = "$instance_url/services/data/v20.0/sobjects/Account/$id";

		$content = json_encode(array("Phone" => $phone, "Type" => $type));
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token", "Content-type: application/json"));
		curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
		curl_setopt($curl, CURLOPT_POSTFIELDS, $content);
		curl_exec($curl);
		$status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

		if ( $status != 204 ) {
			die("Error: call to URL $url failed with status $status, curl_error " . curl_error($curl) . ", curl_errno " . curl_errno($curl));
		}
		echo "HTTP status $status updating account<br/><br/>";
		curl_close($curl);

		return $status;
	}
}
	
	
?>


<!-- HTML file part to display results from functions called -->
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>REST/OAuth Example</title>
</head>


<body>

    <tt>

		<?php
		
			// Call class to use functions in
			$test = new test;
			
			// Setup token and url
			$access_token = $_SESSION['access_token'];
			$instance_url = $_SESSION['instance_url'];
			
			// Call function to show current accounts in Account sObject
			$test->show_accounts($instance_url, $access_token);
			
			// Set up attributes to pass into getBetweenDates method
			$start = ["2018-02-19T23:22:39.000+0000", $instance_url, $access_token];
			$end = "2018-02-19T23:28:52.000+0000";
			$test->getBetweenDates($start, $end);
			
			
			// Set attributes to pass into create method
			$name = "Fallout Store";
			$attributes = [$name, $instance_url, $access_token];
			
			// Call function to create new account and set $id field
			$id = $test->create($attributes);
			
			// Set attributes to pass into update method
			$phone = "555-555-5555";
			$type = "Customer - Direct";
			$attributes = [$phone, $type, $instance_url, $access_token];
			
			// Call to update account
			$status = $test->update($id, $attributes);
			
			// Call function to show current accounts in Account sObject
			$test->show_accounts($instance_url, $access_token);
		?>

    </tt>

</body>

</html>
