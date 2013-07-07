class system-update {
	class { 'apt':
	}
	
	exec { 'apt-update':
		command => '/usr/bin/apt-get update',
	}
	
	Exec['apt-update'] -> Package <| |>
}

class mysql-setup {
	class { 'mysql':
	}
	
	class { 'mysql::php':
	}
	
	class { 'mysql::server':
		config_hash => {
			root_password => 'toor',
		},
	}
	
	mysql::db { 'acme-blog':
		ensure => present,
		user => 'acme',
		password => 'acme',
		grant => ['all'],
		sql => '/vagrant/puppet/manifests/acme-blog.sql',
	}
}

class apache-setup {
	class { 'apache':
		mpm_module => 'prefork',
	}
	
	class { 'apache::mod::php':
	}
	
	apache::vhost { 'acme-blog':
		port => '80',
		docroot => '/vagrant/app',
		default_vhost => true,
	}
}

class app-setup {
}

include system-update
include mysql-setup
include apache-setup
include app-setup
