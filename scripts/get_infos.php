<?php

//global $froxlor_db, $froxlor_host, $froxlor_user, $froxlor_pass;

// DB PDO Object
$db = new PDO('mysql:unix_socket='.$froxlor_host.';dbname='.$froxlor_db.';charset=utf8', $froxlor_user, $froxlor_pass);


function getDomainInfo() {

	global $db;

	// Get domain list
	$domain_stmt = $db->query('SELECT id, domain, iswildcarddomain, wwwserveralias, documentroot, email_only, aliasdomain, ssl_redirect, parentdomainid FROM panel_domains');
	$domains = $domain_stmt->fetchAll(PDO::FETCH_ASSOC);

	// Build domain info array

	$domain_info[] = array();


	foreach($domains as $domain) {
		// Get ip_id
		$ipid_stmt = $db->query('SELECT id_ipandports FROM panel_domaintoip WHERE id_domain='.$domain['id']);
		$_ipid = $ipid_stmt->fetchAll(PDO::FETCH_ASSOC);


		foreach($_ipid as $ipid) {

			// Get ip from ip_id
			$ip_stmt = $db->query('SELECT ip, port FROM panel_ipsandports WHERE id='.$ipid['id_ipandports']);
			$_ip = $ip_stmt->fetch(PDO::FETCH_ASSOC);

			// Push ip and port into array
			$domain['ip'] = $_ip['ip'];
			$domain['port'] = $_ip['port'];

			// Push domain row into domain_info array
			array_push($domain_info, $domain);

		}

	}


	array_shift($domain_info);

	return $domain_info;
}


function getSSLInfo() {

	global $db;

	// Get SSL path customer_ssl_path

	$sslpath_stmt = $db->query('SELECT value FROM panel_settings WHERE varname="customer_ssl_path"');
	$_sslpath = $sslpath_stmt->fetch(PDO::FETCH_ASSOC);
	$sslpath = $_sslpath['value'];

	return $sslpath;
}


function getSSLfromIP($ip) {

        global $db;

	// Get SSL paths from ip settings
        $ip_ssl_stmt = $db->prepare('SELECT ssl_cert_file, ssl_key_file, ssl_ca_file, ssl_cert_chainfile FROM panel_ipsandports WHERE ip=:ip AND port=8843;');
	$ip_ssl_stmt->bindParam(':ip', $ip, PDO::PARAM_STR);
	$ip_ssl_stmt->execute();
	$ip_ssl = $ip_ssl_stmt->fetch(PDO::FETCH_ASSOC);

	return $ip_ssl;
}

function getSSLfromDomainID($domainid) {

	global $db;

	$did_ssl_stmt = $db->prepare('SELECT ssl_cert_file, ssl_key_file, ssl_ca_file, ssl_cert_chainfile FROM domain_ssl_settings WHERE domainid=:id');
	$did_ssl_stmt->bindParam(':id', $domainid, PDO::PARAM_INT);
	$did_ssl_stmt->execute();
	$did_ssl = $did_ssl_stmt->fetch(PDO::FETCH_ASSOC);

	return $did_ssl;
}

function getAliasDomains($domain) {

	global $db;

	$alias_stmt = $db->prepare('SELECT domain, iswildcarddomain, wwwserveralias FROM panel_domains WHERE aliasdomain=:id');
	$alias_stmt->bindParam(':id', $domain['id'], PDO::PARAM_INT);
	$alias_stmt->execute();
	$_alias = $alias_stmt->fetchAll(PDO::FETCH_ASSOC);

	return $_alias;

}

?>
