class system-update {
	class { 'apt':
	}
	
	exec { 'apt-update':
		command => '/usr/bin/apt-get update',
	}
	
	Exec['apt-update'] -> Package <| |>
}

class sendmail-setup {
	package { 'sendmail':
		ensure => installed,
	}
	
	service { 'sendmail':
		require => Package['sendmail'],
		ensure => running,
	}
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
		sql => 'puppet:///manifests/acme-blog.sql',
	}
}

class apache-setup {
	class { 'apache':
		mpm_module => 'prefork',
	}
	
	class { 'apache::mod::php':
	}
	
	apache::vhost { 'acme-blog':
		default_vhost => true,
		port => '80',
		docroot => '/vagrant/app',
		docroot_owner => 'root',
		docroot_group => 'root',
	}
}

class app-setup {
	file { '/vagrant/app/wp-content':
		ensure => directory,
		mode => 0777,
	}
	
	file { '/vagrant/app/wp-content/plugins':
		ensure => directory,
		mode => 0777,
	}
	
	file { '/vagrant/app/wp-content/themes':
		ensure => directory,
		mode => 0777,
	}
	
	file { '/vagrant/app/wp-content/uploads':
		ensure => directory,
		mode => 0777,
	}
}

include system-update
include sendmail-setup
include mysql-setup
include apache-setup
include app-setup
