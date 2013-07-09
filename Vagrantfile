Vagrant.configure("2") do |config|
	config.vm.box = "acme-blog"
	config.vm.box_url = "http://files.vagrantup.com/precise64.box"
	config.vm.hostname  = "acme-blog.local"
	
	config.vm.network :private_network, ip: "192.168.56.103"
	
	config.vm.provision :puppet do |puppet|
		puppet.manifests_path = "puppet/manifests"
		puppet.manifest_file = "vagrant.pp"
		puppet.module_path = "puppet/modules"
	end
end
