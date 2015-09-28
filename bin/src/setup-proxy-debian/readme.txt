Setup utility for Debian 7 64 Bits
---------------------------------------------------------------

Download the Debian 7 amd64 network install ISO.
https://www.debian.org/CD/netinst/#netinst-stable

Use only the network install mode, the setup is able to download all required packages.
Use a minimalist installation ( Only SSH server )

Open a root console and perform these operations:

cd /root
tar xf setup-proxy-debian.tar.gz
chmod 0755 setup-proxy-debian
./setup-proxy-debian

After installation open your browser and navigate trough https://yourserver:9000
