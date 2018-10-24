<?

	require_once(__DIR__.'/REST.php');

	ini_set('log_errors', 1);
	ini_set('display_errors', 1);

	$config = json_decode(file_get_contents(__DIR__.'/config.json'));

	$db = pg_connect($config->pgsql->connect);

	$query = pg_query_params($db, "SELECT * FROM addresses WHERE geocode_completed_when IS NULL", []);

	while($row = pg_fetch_object($query)):
		
		$url = $config->geocod->url->api.$config->geocod->url->geocode;
			
		$data = [
			'api_key' => $config->geocod->apikey,
			'street' => $row->address_street,
			'city' => $row->address_locality,
			'state' => $row->address_division,
			'postal_code' => $row->address_postcode,
			'country' => $row->address_country
		];
			
		$result = REST::factory()->url($url)->data($data)->get();
		
		if($result->info->http_code == 200):
			
			$json = json_decode($result->body);
			
			$a = $json->results[0];
			
			pg_query_params(
				$db,
				"UPDATE addresses SET
					geocode_service_id='geocod',
					geocode_completed_when='now',
					geocode_data=$2,
					geocode_accuracy=$3,
					geocode_latitude=$4,
					geocode_longitude=$5,
					geocode_address_full=$6,
					geocode_address_street=$7,
					geocode_address_locality=$8,
					geocode_address_division=$9,
					geocode_address_postcode=$10,
					geocode_address_country=$11
				WHERE id=$1",
				[$row->id,
					$result->body,
					$a->accuracy,
					$a->location->lat,
					$a->location->lng,
					$a->formatted_address,
					($a->address_components->formatted_street ?? null),
					$a->address_components->city,
					$a->address_components->state,
					$a->address_components->zip,
					$a->address_components->country
				]
			);
		
		endif;
		
	endwhile;

?>