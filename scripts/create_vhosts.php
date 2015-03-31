<?php

// Get www/wildcard alias for domain
function getSubAlias($domain) {

	$domainalias = "";

	if($domain['iswildcarddomain'])
		$domainalias = " *.".$domain['domain'];

	if($domain['wwwserveralias'])
		$domainalias = " www.".$domain['domain'];

	return $domainalias;

}

// Get real alias for domain
function getAlias($domain) {

	$domainalias = " ";

	$aliasdomains = getAliasDomains($domain);

	foreach($aliasdomains as $alias) {
		$domainalias .= $alias['domain']." ";
		$domainalias .= getSubAlias($alias)." ";
	}

	return $domainalias;
}

// Merge cert bundle fror nginx
function mergeSSL($domain) {
	$sslpath = getSSLInfo();
	$domainname = $domain['domain'];
	$filepath = $sslpath.$domainname;

	// get Custom SSL
	$custom_ssl = getSSLfromDomainID($domain['id']);

	// Merge Custom SSL Bundle
	if(!empty($custom_ssl['ssl_cert_file']))
		$cert_bundle = $custom_ssl['ssl_cert_file']."\n";

	if(!empty($custom_ssl['ssl_ca_file']))
		$cert_bundle .= $custom_ssl['ssl_ca_file']."\n";

	if(!empty($custom_ssl['ssl_cert_chainfile']))
		$cert_bundle .= $custom_ssl['ssl_cert_chainfile'];

	if(!empty($cert_bundle)) {
		$ssl_key = $custom_ssl['ssl_key_file'];
		file_put_contents('/etc/ssl/nginx/'.$domainname.'.key', $ssl_key, LOCK_EX);
	}

	// check bundle content - set ip ssl
	if(empty($cert_bundle)) {

		// lookup ssl paths from ip
		$ip_ssl = getSSLfromIP($domain['ip']);

		// get cert, ca and chain
		if(file_exists($ip_ssl['ssl_cert_file']))
			$cert_bundle = file_get_contents($ip_ssl['ssl_cert_file']);

		if(file_exists($ip_ssl['ssl_ca_file']))
			$cert_bundle .= file_get_contents($ip_ssl['ssl_ca_file']);

		if(file_exists($ip_ssl['ssl_cert_chainfile']))
			$cert_bundle .= file_get_contents($ip_ssl['ssl_cert_chainfile']);

		// Copy Key
		if(file_exists($ip_ssl['ssl_key_file'])) {
			$ssl_key = file_get_contents($ip_ssl['ssl_key_file']);
			file_put_contents('/etc/ssl/nginx/'.$domainname.'.key', $ssl_key, LOCK_EX);
		}
	}

	if(empty($cert_bundle))
		return "No SSL";

	// Write Bundle
	file_put_contents('/etc/ssl/nginx/'.$domainname.'.bundle', $cert_bundle, LOCK_EX);

	return "OK";
}

function getvhost($domain) {

	if($domain['ssl_redirect']) {

		$vhost = "server {
	listen 80;

	server_name ".$domain['domain'].getSubAlias($domain).getAlias($domain).";
	access_log /var/log/nginx/".$domain['domain'].".access.log;

	rewrite ^(.*) https://".$domain['domain']."$1 permanent;

}";

	} elseif (preg_match('/^https?\:\/\//', $domain['documentroot'])) {

		$vhost = "server {
	listen 80;

	server_name ".$domain['domain'].getSubAlias($domain).getAlias($domain).";
	access_log /var/log/nginx/".$domain['domain'].".access.log;

	rewrite ^(.*) ".$domain['documentroot']."$1 permanent;

}";

	} else {

		$vhost = "server {
	listen 80;

	server_name ".$domain['domain'].getSubAlias($domain).getAlias($domain).";
	access_log /var/log/nginx/".$domain['domain'].".access.log;

	root ".$domain['documentroot'].";
	index index.php index.html index.htm;

	location ~ /\.ht {
		deny all;
	}

	location ~* \.(js|css|gif|jpe?g|png|ico|swf)$ {
		expires 10d;
		add_header Cache-Control \"public\";
		try_files \$uri \$uri/ @proxy;
	}

	location / {
		try_files \$uri \$uri/ @proxy;
        }

        location @proxy {
                proxy_pass http://\$server_addr:8888\$request_uri;
                include /etc/nginx/proxy.conf;
				";
				
		
		
		
		
		$vhost .="
        }

	location ~ \.(php|php5|cgi|pl|htm?l)$ {
		proxy_pass http://\$server_addr:8888\$request_uri;		
		include /etc/nginx/proxy.conf;
		";


		

		$vhost .="
		}
}


}

";

	}

	return $vhost;

}

function getSSLvhost($domain) {

        if (preg_match('/^https?\:\/\//', $domain['documentroot'])) {

		$vhost = "server {
	listen 443 ssl spdy;
	server_name ".$domain['domain'].getSubAlias($domain).getAlias($domain).";
	ssl on;
	ssl_certificate /etc/ssl/nginx/".$domain['domain'].'.bundle'.";
	ssl_certificate_key /etc/ssl/nginx/".$domain['domain'].'.key'.";
	ssl_client_certificate /etc/ssl/nginx/".$domain['domain'].'.bundle'.";
	ssl_verify_client off;
	ssl_prefer_server_ciphers on;
	#ssl_protocols TLSv1;
	ssl_session_cache shared:SSL:10m;
	ssl_session_timeout 5m;
	access_log /var/log/nginx/".$domain['domain'].".access.log;

	rewrite ^(.*) ".$domain['documentroot']."$1 permanent;

}";

        } else {

		$vhost = "server {
	listen 443 ssl spdy;
	server_name ".$domain['domain'].getSubAlias($domain).getAlias($domain).";
	ssl on;
	ssl_certificate /etc/ssl/nginx/".$domain['domain'].'.bundle'.";
	ssl_certificate_key /etc/ssl/nginx/".$domain['domain'].'.key'.";
	ssl_client_certificate /etc/ssl/nginx/".$domain['domain'].'.bundle'.";
	ssl_verify_client off;
	ssl_prefer_server_ciphers on;
	#ssl_protocols TLSv1;
	ssl_session_cache shared:SSL:10m;
	ssl_session_timeout 5m;
	access_log /var/log/nginx/".$domain['domain'].".access.log;

	root ".$domain['documentroot'].";
	index index.php index.html index.htm;

	location ~ /\.ht {
		deny all;
	}

	location ~* \.(js|css|gif|jpe?g|png|ico|swf)$ {
		expires 10d;
		add_header Cache-Control \"public\";
		try_files \$uri \$uri/ @proxy;
	}

        location / {
                try_files \$uri \$uri/ @proxy;
        }

        location @proxy {
                proxy_pass https://\$server_addr:8843\$request_uri;
                include /etc/nginx/proxy.conf;
				";


		
		

		$vhost .="
        }

	location ~ \.(php|php5|cgi|pl|htm?l)$ {
		proxy_pass https://\$server_addr:8843\$request_uri;
		include /etc/nginx/proxy.conf;
		";


		
		

		$vhost .="
	}

}";

	}

	return $vhost;
}

// Create vhosts
function create_vhosts($_domaininfo) {

	exec("/bin/rm /etc/nginx/sites-enabled/*froxlor*.conf");

	foreach($_domaininfo as $domain) {

		// exit if no domain
		if(empty($domain))
			continue;

		// exit if email only
		if($domain['email_only'])
			continue;

		// if alias for other domain
		if($domain['aliasdomain'])
			continue;

		// set prefix
		if($domain['parentdomainid'] != 0)
		{
			$file_prefix = "20_froxlor";
		}

		if($domain['parentdomainid'] == 0)
		{
			$file_prefix = "22_froxlor";
		}

		if($domain['port'] == 8888) {

			$vhost = getvhost($domain);

			if(!empty($domain['domain']))
				file_put_contents(NGINX_VHOST_PATH.'/'.$file_prefix.'_normal_vhost_'.$domain['domain'].'.conf', $vhost, LOCK_EX);
		}

                if($domain['port'] == 8843) {

			if(mergeSSL($domain) != "OK")
				continue;

			$vhost = getSSLvhost($domain);

	                if(!empty($domain['domain']))
        	                file_put_contents(NGINX_VHOST_PATH.'/'.$file_prefix.'_ssl_vhost_'.$domain['domain'].'.conf', $vhost, LOCK_EX);
                }

	}

	exec("/etc/init.d/nginx reload");
}

?>
