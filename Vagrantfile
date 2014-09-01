# -*- mode: ruby -*-
# vi: set ft=ruby :

# Copyright Siemens AG, 2014
# SPDX-License-Identifier:  GPL-2.0 LGPL-2.1

$build_and_test = <<SCRIPT
echo "Provisioning system to compile, test and develop."
# fix "dpkg-reconfigure: unable to re-open stdin: No file or directory" issue
sudo dpkg-reconfigure locales

sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq -y
sudo DEBIAN_FRONTEND=noninteractive apt-get upgrade -qq -y

sudo apt-get install -qq debhelper libglib2.0-dev libmagic-dev libxml2-dev libtext-template-perl librpm-dev subversion rpm libpcre3-dev libssl-dev
sudo apt-get install -qq php5-pgsql php-pear php5-cli
sudo apt-get install -qq apache2 libapache2-mod-php5
sudo apt-get install -qq binutils bzip2 cabextract cpio sleuthkit genisoimage poppler-utils rpm upx-ucl unrar-free zip unzip p7zip-full p7zip
sudo apt-get install -qq wget curl subversion git
sudo apt-get install -qq libpq-dev postgresql

curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

date > /etc/vagrant.provisioned

echo "lets go!"
cd /vagrant

make
sudo make install
sudo /usr/local/lib/fossology/fo-postinstall
sudo /etc/init.d/fossology start

sudo cp /vagrant/install/src-install-apache-example.conf /etc/apache2/conf.d/fossology.conf

sudo /etc/init.d/apache2 restart

sudo addgroup vagrant fossy

# let's scan some spdx license lists
SPDX_VERSIONS="1.19 1.18 1.17 1.16 1.15 1.14"
for i in $SPDX_VERSIONS; do
 wget -q https://github.com/siemens/spdx-licenselist/archive/v$i.zip -O /tmp/v$i.zip
 # remove irrelevant files, how to tag them inside FOSSology?
 zip --delete /tmp/v$i.zip "*.xls"
 zip --delete /tmp/v$i.zip "*.ods"
 cp2foss -v  --username fossy --password fossy -q all /tmp/v$i.zip
done

echo "use your FOSSology at http://localhost:8081/repo/"
echo " user: fossy , password: fossy
echo "or do a vagrant ssh and look at /vagrant for your source tree"
SCRIPT

Vagrant.configure("2") do |config|
  # Hmm... no Debian image available yet, let's use a derivate
  # Ubuntu 12.04 LTS (Precise Pangolin)
  config.vm.box = "precise64"
  config.vm.box_url = "http://cloud-images.ubuntu.com/precise/current/precise-server-cloudimg-vagrant-amd64-disk1.box"

  config.vm.provider :virtualbox do |vbox|
    vbox.customize ["modifyvm", :id, "--memory", "1024"]
    vbox.customize ["modifyvm", :id, "--cpus", "2"]
  end

  config.vm.network :forwarded_port, guest: 80, host: 8081

  # call the script
  config.vm.provision :shell, :inline => $build_and_test

  # fix "stdin: is not a tty" issue
  config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"
end
