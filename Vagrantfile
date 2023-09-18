Vagrant.require_version ">= 2.2.19"
Vagrant.configure(2) do |config|
    # Base box
    config.vm.box = "ubuntu/jammy64"

    # Port forwardings
    config.vm.network "forwarded_port", guest: 8002, host: 8002, id: "http"

    # Virtual machine details
    config.vm.provider "virtualbox" do |vb|
        vb.gui = false
        vb.cpus = 4
        vb.memory = 8192
        vb.name = "Ylilauta-MediaServer"
        vb.default_nic_type = "virtio"
        vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]

        if Vagrant::Util::Platform.windows? then
            vb.customize ["modifyvm", :id, "--uartmode1", "client", "NUL"]
        else
            vb.customize ["modifyvm", :id, "--uartmode1", "file", "/dev/null"]
        end
    end

    config.vm.synced_folder ".", "/vagrant", disabled: false

    # Provisioning
    config.vm.provision "shell", path: "vagrant-provision.sh"
end